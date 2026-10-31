<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Session;
use App\Helpers\Response;
use App\Config\App;

class AuthMiddleware
{
    /**
     * Verifica autenticação. Redireciona para login (páginas) ou retorna 401 (APIs).
     */
    public static function check(bool $isApi = false): void
    {
        if (!Session::isLoggedIn()) {
            if ($isApi) {
                Response::unauthorized('Sessão expirada. Faça login novamente.');
            } else {
                Session::flash('info', 'Sua sessão expirou. Por favor, faça login novamente.');
                self::redirectLogin();
            }
        }

        // Verifica se a conta do usuário ainda está ativa (pode ter sido desativada)
        self::verificarContaAtiva($isApi);

        // Senha provisoria do superadmin: so libera a tela de troca de senha
        self::exigirTrocaDeSenha($isApi);
    }

    /**
     * Restringe acesso por perfil.
     */
    public static function requirePerfil(string|array $perfis, bool $isApi = false): void
    {
        self::check($isApi);

        $perfisPermitidos = is_array($perfis) ? $perfis : [$perfis];
        $perfilAtual      = Session::getPerfil();

        if (!in_array($perfilAtual, $perfisPermitidos, true)) {
            if ($isApi) {
                Response::forbidden('Você não tem permissão para esta ação.');
            } else {
                Session::flash('error', 'Você não tem permissão para acessar esta página.');
                Response::redirect('/dashboard.php');
            }
        }
    }

    public static function requireSuperadmin(bool $isApi = false): void
    {
        self::requirePerfil(App::PERFIL_SUPERADMIN, $isApi);
    }

    public static function requireAdmin(bool $isApi = false): void
    {
        self::requirePerfil([App::PERFIL_ADMINISTRADOR, App::PERFIL_SUPERADMIN], $isApi);
    }

    public static function requireDiretor(bool $isApi = false): void
    {
        self::requirePerfil(App::PERFIS_OPERACIONAIS_ESCOLA, $isApi);
    }

    public static function requireOportunidadesEscola(bool $isApi = false): void
    {
        self::requirePerfil(App::PERFIS_OPORTUNIDADES_ESCOLA, $isApi);
    }

    public static function requireSecretario(bool $isApi = false): void
    {
        self::requirePerfil([App::PERFIL_SECRETARIO, App::PERFIL_SUPERADMIN], $isApi);
    }

    // ----------------------------------------------------------------
    // Privados
    // ----------------------------------------------------------------

    /**
     * Verifica periodicamente (a cada 5 min) se a conta ainda está ativa/não bloqueada.
     * Evita que um usuário desativado pelo superadmin continue navegando.
     */
    private static function verificarContaAtiva(bool $isApi): void
    {
        $idUsuario = Session::getUsuarioId();
        if (!$idUsuario) return;

        // Só verifica a cada 5 minutos para não sobrecarregar o banco
        $ultimaVerif = Session::get('ultima_verif_conta', 0);
        if ((time() - $ultimaVerif) < 300) return;

        Session::set('ultima_verif_conta', time());

        try {
            $db   = \App\Config\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT ativo, bloqueado FROM usuarios WHERE id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $idUsuario]);
            $row = $stmt->fetch();

            if (!$row || !(bool)$row['ativo'] || (bool)$row['bloqueado']) {
                Session::destroy();
                if ($isApi) {
                    Response::unauthorized('Sua conta foi desativada ou bloqueada. Contate o administrador.');
                } else {
                    self::redirectLogin();
                }
            }
        } catch (\Throwable $e) {
            // Em caso de erro de banco, não derruba a sessão
        }
    }

    /**
     * Superadmin com senha provisoria: libera apenas Meu Perfil, a troca de
     * senha, o logout e a renovacao de sessao. O resto e redirecionado/barrado.
     */
    private static function exigirTrocaDeSenha(bool $isApi): void
    {
        if (!Session::precisaTrocarSenha()) return;

        $liberados = [
            '/pages/meu-perfil.php',
            '/api/auth/alterar-senha.php',
            '/api/auth/logout.php',
            '/api/auth/renovar-sessao.php',
        ];
        if (in_array($_SERVER['SCRIPT_NAME'] ?? '', $liberados, true)) return;

        if ($isApi) {
            Response::forbidden('Troque a senha provisória para continuar.');
        } else {
            Session::flash('info', 'Por segurança, troque a senha provisória antes de continuar.');
            Response::redirect('/pages/meu-perfil.php');
        }
    }

    private static function redirectLogin(): never
    {
        header('Location: /index.php');
        exit;
    }
}
