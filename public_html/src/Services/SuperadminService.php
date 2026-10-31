<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\{EscolaRepository, UsuarioRepository, ProdutoRepository, UnidadeRepository, SugestaoRepository};
use App\Config\App;
use App\Services\AuditoriaService;

class SuperadminService
{
    private EscolaRepository   $escolas;
    private UsuarioRepository  $usuarios;
    private ProdutoRepository  $produtos;
    private UnidadeRepository  $unidades;
    private SugestaoRepository $sugestoes;

    public function __construct()
    {
        $this->escolas   = new EscolaRepository();
        $this->usuarios  = new UsuarioRepository();
        $this->produtos  = new ProdutoRepository();
        $this->unidades  = new UnidadeRepository();
        $this->sugestoes = new SugestaoRepository();
    }

    // ================================================================
    // ESCOLAS
    // ================================================================

    public function listarEscolas(bool $apenasAtivas = false): array
    {
        return $this->escolas->listar($apenasAtivas);
    }

    public function criarEscola(array $dados): array
    {
        $nome   = trim($dados['nome']   ?? '');
        $codigo = strtoupper(trim($dados['codigo'] ?? ''));

        if (empty($nome))   return $this->fail('O nome da escola é obrigatório.');
        if (empty($codigo)) return $this->fail('O código da escola é obrigatório.');
        if (strlen($nome) > 150) return $this->fail('Nome muito longo (máx. 150 caracteres).');
        if ($this->escolas->codigoExiste($codigo)) return $this->fail("O código '{$codigo}' já está em uso.");

        $id = $this->escolas->criar([
            'nome'     => $nome,
            'codigo'   => $codigo,
            'endereco' => trim($dados['endereco'] ?? ''),
            'telefone' => trim($dados['telefone'] ?? ''),
        ]);

        AuditoriaService::escola('criar', $id, "Escola '{$nome}' criada.");
        return $this->ok("Escola '{$nome}' criada com sucesso.", ['id' => $id]);
    }

    public function atualizarEscola(int $id, array $dados): array
    {
        $escola = $this->escolas->buscarPorId($id);
        if (!$escola) return $this->fail('Escola não encontrada.');

        $nome   = trim($dados['nome']   ?? '');
        $codigo = strtoupper(trim($dados['codigo'] ?? ''));

        if (empty($nome))   return $this->fail('O nome da escola é obrigatório.');
        if (empty($codigo)) return $this->fail('O código da escola é obrigatório.');
        if ($this->escolas->codigoExiste($codigo, $id)) return $this->fail("O código '{$codigo}' já está em uso.");

        $this->escolas->atualizar($id, [
            'nome'     => $nome,
            'codigo'   => $codigo,
            'endereco' => trim($dados['endereco'] ?? ''),
            'telefone' => trim($dados['telefone'] ?? ''),
        ]);

        AuditoriaService::escola('editar', $id, "Escola atualizada.");
        return $this->ok('Escola atualizada com sucesso.');
    }

    public function alternarAtivoEscola(int $id): array
    {
        $escola = $this->escolas->buscarPorId($id);
        if (!$escola) return $this->fail('Escola não encontrada.');

        $novoAtivo = $this->escolas->alternarAtivo($id);
        $msg = $novoAtivo ? 'Escola ativada.' : 'Escola desativada.';
        return $this->ok($msg, ['ativo' => $novoAtivo]);
    }

    // ================================================================
    // USUÁRIOS
    // ================================================================

    public function listarUsuarios(?string $perfil = null): array
    {
        return $this->usuarios->listar($perfil);
    }

    public function criarUsuario(array $dados): array
    {
        $erros = $this->validarDadosUsuario($dados);
        if ($erros) return $this->fail($erros);

        $perfil    = $dados['perfil'];
        $categoria = $perfil === App::PERFIL_ADMINISTRADOR ? ($dados['categoria'] ?? null) : null;
        $idEscola  = in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) ? ($dados['id_escola'] ?? null) : null;

        // Valida vínculo com escola para diretor
        if ($perfil === App::PERFIL_DIRETOR) {
            if (!$idEscola) return $this->fail('Selecione a escola para o diretor.');
            if ($this->usuarios->escolaJaTemDiretor((int) $idEscola)) {
                return $this->fail('Esta escola já possui um diretor ativo.');
            }
        }

        // Valida vínculo com escola para escriturário e estagiário (sem exclusividade)
        if (in_array($perfil, [App::PERFIL_ESCRITURARIO, App::PERFIL_ESTAGIARIO], true) && !$idEscola) {
            return $this->fail('Selecione a escola para este perfil.');
        }

        // Valida categoria para administrador
        if ($perfil === App::PERFIL_ADMINISTRADOR && !in_array($categoria, ['produto', 'servico'], true)) {
            return $this->fail('Selecione a categoria gerenciada pelo administrador.');
        }

        // Verifica unicidade
        if ($this->usuarios->usernameExisteVerify($dados['username'])) {
            return $this->fail("O nome de usuário '{$dados['username']}' já está em uso.");
        }
        if ($this->usuarios->emailExiste($dados['email'])) {
            return $this->fail("O e-mail '{$dados['email']}' já está cadastrado.");
        }

        $senhaHash = password_hash($dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]);

        $id = $this->usuarios->criar([
            'nome'      => trim($dados['nome']),
            'username'  => trim($dados['username']),
            'email'     => trim($dados['email']),
            'senha_hash'=> $senhaHash,
            'perfil'    => $perfil,
            'categoria' => $categoria,
            'id_escola' => $idEscola ? (int) $idEscola : null,
        ]);

        AuditoriaService::usuario('criar', $id, "Usuario '{$dados['nome']}' criado com perfil {$perfil}.");
        return $this->ok("Usuário '{$dados['nome']}' criado com sucesso.", ['id' => $id]);
    }

    public function atualizarUsuario(int $id, array $dados): array
    {
        $usuario = $this->usuarios->buscarPorId($id);
        if (!$usuario) return $this->fail('Usuário não encontrado.');

        $erros = $this->validarDadosUsuario($dados, $id);
        if ($erros) return $this->fail($erros);

        $perfil    = $dados['perfil'];
        $categoria = $perfil === App::PERFIL_ADMINISTRADOR ? ($dados['categoria'] ?? null) : null;
        $idEscola  = in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) ? ($dados['id_escola'] ?? null) : null;

        if ($perfil === App::PERFIL_DIRETOR && $idEscola) {
            if ($this->usuarios->escolaJaTemDiretor((int) $idEscola, $id)) {
                return $this->fail('Esta escola já possui um diretor ativo.');
            }
        }
        if ($perfil === App::PERFIL_ADMINISTRADOR && !in_array($categoria, ['produto', 'servico'], true)) {
            return $this->fail('Selecione a categoria gerenciada.');
        }

        if ($this->usuarios->usernameExisteVerify($dados['username'], $id)) {
            return $this->fail("O nome de usuário '{$dados['username']}' já está em uso.");
        }
        if ($this->usuarios->emailExiste($dados['email'], $id)) {
            return $this->fail("O e-mail '{$dados['email']}' já está cadastrado.");
        }

        $this->usuarios->atualizar($id, [
            'nome'      => trim($dados['nome']),
            'username'  => trim($dados['username']),
            'email'     => trim($dados['email']),
            'perfil'    => $perfil,
            'categoria' => $categoria,
            'id_escola' => $idEscola ? (int) $idEscola : null,
        ]);

        AuditoriaService::usuario('editar', $id, "Usuario '{$dados['nome']}' atualizado.");
        return $this->ok('Usuário atualizado com sucesso.');
    }

    public function alternarAtivoUsuario(int $id): array
    {
        $usuario = $this->usuarios->buscarPorId($id);
        if (!$usuario) return $this->fail('Usuário não encontrado.');

        $novoAtivo = $this->usuarios->alternarAtivo($id);
        $msg = $novoAtivo ? 'Usuário ativado.' : 'Usuário desativado.';
        AuditoriaService::usuario($novoAtivo ? 'ativar' : 'desativar', $id, "Usuario '{$usuario['nome']}' " . ($novoAtivo ? 'ativado' : 'desativado') . ".");
        return $this->ok($msg, ['ativo' => $novoAtivo]);
    }

    // ================================================================
    // PRODUTOS
    // ================================================================

    public function listarProdutos(bool $apenasAtivos = false): array
    {
        return $this->produtos->listar($apenasAtivos);
    }

    public function criarProduto(array $dados): array
    {
        $nome = trim($dados['nome'] ?? '');
        if (empty($nome)) return $this->fail('O nome do produto é obrigatório.');
        if (strlen($nome) > 150) return $this->fail('Nome muito longo (máx. 150 caracteres).');
        if ($this->produtos->nomeExiste($nome)) return $this->fail("O produto '{$nome}' já está cadastrado.");

        $categoria = trim($dados['categoria'] ?? 'outros');
        if (!in_array($categoria, ['papelaria','higiene','limpeza','outros'], true)) {
            $categoria = 'outros';
        }

        $desc = trim($dados['descricao'] ?? '');
        if (strlen($desc) > 400) return $this->fail('Descricao muito longa (max. 400 caracteres).');

        $id = $this->produtos->criar(['nome' => $nome, 'categoria' => $categoria, 'descricao' => $desc ?: null]);
        return $this->ok("Produto '{$nome}' cadastrado com sucesso.", ['id' => $id]);
    }

    public function atualizarProduto(int $id, array $dados): array
    {
        if (!$this->produtos->buscarPorId($id)) return $this->fail('Produto não encontrado.');

        $nome = trim($dados['nome'] ?? '');
        if (empty($nome)) return $this->fail('O nome do produto é obrigatório.');
        if ($this->produtos->nomeExiste($nome, $id)) return $this->fail("O produto '{$nome}' já está cadastrado.");

        $categoria = trim($dados['categoria'] ?? 'outros');
        if (!in_array($categoria, ['papelaria','higiene','limpeza','outros'], true)) {
            $categoria = 'outros';
        }

        $desc = trim($dados['descricao'] ?? '');
        if (strlen($desc) > 400) return $this->fail('Descricao muito longa (max. 400 caracteres).');

        $this->produtos->atualizar($id, ['nome' => $nome, 'categoria' => $categoria, 'descricao' => $desc ?: null]);
        return $this->ok('Produto atualizado com sucesso.');
    }

    public function alternarAtivoProduto(int $id): array
    {
        if (!$this->produtos->buscarPorId($id)) return $this->fail('Produto não encontrado.');
        $novoAtivo = $this->produtos->alternarAtivo($id);
        return $this->ok($novoAtivo ? 'Produto ativado.' : 'Produto desativado.', ['ativo' => $novoAtivo]);
    }

    // ================================================================
    // UNIDADES DE MEDIDA
    // ================================================================

    public function listarUnidades(bool $apenasAtivas = false): array
    {
        return $this->unidades->listar($apenasAtivas);
    }

    public function criarUnidade(array $dados): array
    {
        $nome  = trim($dados['nome']  ?? '');
        $sigla = mb_strtolower(trim($dados['sigla'] ?? ''), 'UTF-8');

        if (empty($nome))  return $this->fail('O nome da unidade é obrigatório.');
        if (empty($sigla)) return $this->fail('A sigla da unidade é obrigatória.');
        if (strlen($sigla) > 10) return $this->fail('Sigla muito longa (máx. 10 caracteres).');
        if ($this->unidades->siglaExiste($sigla)) return $this->fail("A sigla '{$sigla}' já está em uso.");

        $id = $this->unidades->criar(['nome' => $nome, 'sigla' => $sigla]);
        return $this->ok("Unidade '{$nome} ({$sigla})' cadastrada.", ['id' => $id]);
    }

    public function atualizarUnidade(int $id, array $dados): array
    {
        if (!$this->unidades->buscarPorId($id)) return $this->fail('Unidade não encontrada.');

        $nome  = trim($dados['nome']  ?? '');
        $sigla = mb_strtolower(trim($dados['sigla'] ?? ''), 'UTF-8');

        if (empty($nome))  return $this->fail('O nome da unidade é obrigatório.');
        if (empty($sigla)) return $this->fail('A sigla da unidade é obrigatória.');
        if ($this->unidades->siglaExiste($sigla, $id)) return $this->fail("A sigla '{$sigla}' já está em uso.");

        $this->unidades->atualizar($id, ['nome' => $nome, 'sigla' => $sigla]);
        return $this->ok('Unidade atualizada com sucesso.');
    }

    public function alternarAtivoUnidade(int $id): array
    {
        if (!$this->unidades->buscarPorId($id)) return $this->fail('Unidade não encontrada.');

        if ($this->unidades->estaEmUso($id)) {
            return $this->fail('Esta unidade está em uso em solicitações e não pode ser desativada.');
        }
        $novoAtivo = $this->unidades->alternarAtivo($id);
        return $this->ok($novoAtivo ? 'Unidade ativada.' : 'Unidade desativada.', ['ativo' => $novoAtivo]);
    }

    // ================================================================
    // SUGESTÕES DE PRODUTOS
    // ================================================================

    public function listarSugestoes(?string $status = null): array
    {
        return $this->sugestoes->listar($status);
    }

    public function analisarSugestao(int $id, string $acao): array
    {
        $sugestao = $this->sugestoes->buscarPorId($id);
        if (!$sugestao) return $this->fail('Sugestão não encontrada.');
        if ($sugestao['status'] !== 'pendente') return $this->fail('Esta sugestão já foi analisada.');
        if (!in_array($acao, ['analisada', 'rejeitada'], true)) return $this->fail('Ação inválida.');

        $this->sugestoes->atualizarStatus($id, $acao);
        $msg = $acao === 'analisada' ? 'Sugestão marcada como analisada.' : 'Sugestão rejeitada.';
        return $this->ok($msg);
    }

    // ================================================================
    // Privados
    // ================================================================

    private function validarDadosUsuario(array $dados, ?int $ignorarId = null): ?string
    {
        $nome     = trim($dados['nome']     ?? '');
        $username = trim($dados['username'] ?? '');
        $email    = trim($dados['email']    ?? '');
        $perfil   = $dados['perfil']        ?? '';

        if (empty($nome))     return 'O nome completo é obrigatório.';
        if (strlen($nome) > 100) return 'Nome muito longo (máx. 100 caracteres).';
        if (empty($username)) return 'O nome de usuário é obrigatório.';
        if (!preg_match('/^[a-z0-9._-]{3,60}$/i', $username)) {
            return 'Nome de usuário inválido. Use letras, números, ponto, hífen ou underscore (3–60 caracteres).';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'E-mail inválido.';
        }
        if (!in_array($perfil, [App::PERFIL_ADMINISTRADOR, App::PERFIL_DIRETOR, App::PERFIL_SECRETARIO, App::PERFIL_ESCRITURARIO, App::PERFIL_ESTAGIARIO], true)) {
            return 'Perfil inválido.';
        }

        // Senha obrigatória apenas na criação
        if (!$ignorarId) {
            $senha = $dados['senha'] ?? '';
            if (strlen($senha) < 8) return 'A senha deve ter no mínimo 8 caracteres.';
            if (!preg_match('/[A-Z]/', $senha)) return 'A senha deve conter pelo menos uma letra maiúscula.';
            if (!preg_match('/[0-9]/', $senha)) return 'A senha deve conter pelo menos um número.';
        }

        return null;
    }

    private function ok(string $message, array $data = []): array
    {
        return ['success' => true, 'message' => $message, 'data' => $data];
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message, 'data' => []];
    }
}
