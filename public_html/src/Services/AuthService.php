<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuthRepository;
use App\Repositories\EscolaRepository;
use App\Helpers\Session;
use App\Config\App;
use App\Services\AuditoriaService;

class AuthService
{
    private AuthRepository $repo;
    private EscolaRepository $escolaRepo;

    public function __construct()
    {
        $this->repo       = new AuthRepository();
        $this->escolaRepo = new EscolaRepository();
    }

    /**
     * Realiza o login com controle de tentativas e bloqueio.
     *
     * @return array{success: bool, message: string, data: array}
     */
    public function login(string $identifier, string $senha, string $ip, string $userAgent): array
    {
        $identifier = trim($identifier);

        if (empty($identifier) || empty($senha)) {
            return $this->fail('Usuário e senha são obrigatórios.');
        }

        $usuario = $this->repo->findByUsernameOrEmail($identifier);

        if (!$usuario) {
            AuditoriaService::registrar('login_falha', 'usuario', "Tentativa de login com identificador inexistente: {$identifier}", null, 0, 'anonimo');
            // Não revela se o usuário existe
            return $this->fail('Usuário ou senha incorretos.');
        }

        // Conta bloqueada
        if ((bool) $usuario['bloqueado']) {
            return $this->fail(
                'Sua conta está bloqueada por excesso de tentativas. Contate o administrador.',
                ['bloqueado' => true]
            );
        }

        // Conta inativa
        if (!(bool) $usuario['ativo']) {
            return $this->fail('Sua conta está desativada. Contate o administrador.');
        }

        // Senha incorreta
        if (!password_verify($senha, $usuario['senha_hash'])) {
            $this->repo->incrementarTentativas($usuario['id']);

            $tentativas  = (int) $usuario['tentativas_login'] + 1;
            $restantes   = App::MAX_LOGIN_ATTEMPTS - $tentativas;

            // Bloqueia ao atingir limite
            if ($tentativas >= App::MAX_LOGIN_ATTEMPTS) {
                $this->repo->bloquearConta($usuario['id']);
                AuditoriaService::registrar('conta_bloqueada', 'usuario', "Conta bloqueada apos " . App::MAX_LOGIN_ATTEMPTS . " tentativas incorretas.", $usuario['id'], $usuario['id'], $usuario['perfil']);
                return $this->fail(
                    'Conta bloqueada após ' . App::MAX_LOGIN_ATTEMPTS . ' tentativas incorretas. Contate o administrador.',
                    ['bloqueado' => true]
                );
            }

            $msg = $restantes === 1
                ? 'Usuário ou senha incorretos. Mais 1 tentativa antes do bloqueio.'
                : "Usuário ou senha incorretos. Restam {$restantes} tentativas.";

            AuditoriaService::registrar('login_falha', 'usuario', "Senha incorreta para '{$usuario['nome']}' ({$tentativas}/" . App::MAX_LOGIN_ATTEMPTS . " tentativas).", $usuario['id'], $usuario['id'], $usuario['perfil']);

            return $this->fail($msg, ['tentativas_restantes' => $restantes]);
        }

        // Escola inativa: usuario vinculado a uma escola desativada nao pode acessar
        if (!empty($usuario['id_escola']) && !$this->escolaRepo->estaAtiva((int) $usuario['id_escola'])) {
            AuditoriaService::registrar('login_escola_inativa', 'usuario', "Tentativa de acesso com escola inativa.", $usuario['id'], $usuario['id'], $usuario['perfil']);
            return $this->fail(
                'Seu acesso foi autenticado, mas a sua escola esta temporariamente indisponivel no sistema. Entre em contato com o suporte da Secretaria de Ensino.',
                ['escola_inativa' => true]
            );
        }

        // Login bem-sucedido
        $this->repo->registrarLogin($usuario['id']);
        AuditoriaService::registrar('login_sucesso', 'usuario', "Login realizado com sucesso.", $usuario['id'], $usuario['id'], $usuario['perfil']);

        // Gera token de sessão
        $sessionToken = bin2hex(random_bytes(32));
        $this->repo->salvarSessao($usuario['id'], $sessionToken, $ip, $userAgent);

        // Inicia sessão PHP
        Session::login($usuario);
        Session::set('session_token', $sessionToken);

        return [
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data'    => [
                'perfil'   => $usuario['perfil'],
                'redirect' => '/dashboard.php',
            ],
        ];
    }

    /**
     * Realiza o logout.
     */
    public function logout(): void
    {
        $idUsuario = Session::getUsuarioId();
        if ($idUsuario) {
            $this->repo->removerSessao($idUsuario);
        }
        Session::destroy();
    }

    /**
     * Redefine a senha do próprio usuário logado.
     *
     * @return array{success: bool, message: string, data: array}
     */
    public function redefinirSenhaPropria(int $idUsuario, string $senhaAtual, string $novaSenha, string $confirmacao): array
    {
        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmacao)) {
            return $this->fail('Todos os campos são obrigatórios.');
        }

        if ($novaSenha !== $confirmacao) {
            return $this->fail('A nova senha e a confirmação não coincidem.');
        }

        $erro = $this->validarForcaSenha($novaSenha);
        if ($erro) {
            return $this->fail($erro);
        }

        $usuario = $this->repo->findById($idUsuario);
        if (!$usuario) {
            return $this->fail('Usuário não encontrado.');
        }

        // Busca hash atual (findById não retorna hash por segurança)
        $repo    = new AuthRepository();
        $completo = $repo->findByUsernameOrEmail($usuario['username']);

        if (!password_verify($senhaAtual, $completo['senha_hash'])) {
            return $this->fail('Senha atual incorreta.');
        }

        // Troca obrigatoria (senha provisoria): a nova senha nao pode ser igual a atual
        if (Session::precisaTrocarSenha() && password_verify($novaSenha, $completo['senha_hash'])) {
            return $this->fail('A nova senha deve ser diferente da senha provisória.');
        }

        $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->repo->atualizarSenha($idUsuario, $novoHash);
        Session::set('trocar_senha', false);

        return [
            'success' => true,
            'message' => 'Senha alterada com sucesso.',
            'data'    => [],
        ];
    }

    /**
     * Superadmin redefine senha de qualquer usuário (também desbloqueia).
     *
     * @return array{success: bool, message: string, data: array}
     */
    public function redefinirSenhaPorAdmin(int $idAlvo, string $novaSenha, string $confirmacao): array
    {
        if (empty($novaSenha) || empty($confirmacao)) {
            return $this->fail('Todos os campos são obrigatórios.');
        }

        if ($novaSenha !== $confirmacao) {
            return $this->fail('A nova senha e a confirmação não coincidem.');
        }

        $erro = $this->validarForcaSenha($novaSenha);
        if ($erro) {
            return $this->fail($erro);
        }

        $usuario = $this->repo->findById($idAlvo);
        if (!$usuario) {
            return $this->fail('Usuário não encontrado.');
        }

        $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->repo->desbloquearConta($idAlvo, $novoHash);

        return [
            'success' => true,
            'message' => "Senha de {$usuario['nome']} redefinida com sucesso.",
            'data'    => [],
        ];
    }

    /**
     * Lista contas bloqueadas.
     */
    public function listarBloqueados(): array
    {
        return $this->repo->listarBloqueados();
    }

    // ----------------------------------------------------------------
    // Privados
    // ----------------------------------------------------------------

    private function fail(string $message, array $data = []): array
    {
        return ['success' => false, 'message' => $message, 'data' => $data];
    }

    /**
     * Regras mínimas de senha:
     * - Mínimo 8 caracteres
     * - Pelo menos 1 letra maiúscula
     * - Pelo menos 1 número
     */
    private function validarForcaSenha(string $senha): ?string
    {
        if (strlen($senha) < 8) {
            return 'A senha deve ter no mínimo 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            return 'A senha deve conter pelo menos uma letra maiúscula.';
        }
        if (!preg_match('/[0-9]/', $senha)) {
            return 'A senha deve conter pelo menos um número.';
        }
        return null;
    }
}
