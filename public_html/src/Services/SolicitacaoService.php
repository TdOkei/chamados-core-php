<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\{SolicitacaoRepository, UsuarioRepository, OportunidadeRepository, ComentarioRepository};
use App\Config\App;
use App\Services\AuditoriaService;
use App\Services\CacheService;

class SolicitacaoService
{
    private SolicitacaoRepository $repo;
    private UsuarioRepository     $usuarioRepo;
    private NotificacaoService    $notif;
    private ComentarioRepository  $comentarios;

    public function __construct()
    {
        $this->repo        = new SolicitacaoRepository();
        $this->usuarioRepo = new UsuarioRepository();
        $this->notif       = new NotificacaoService();
        $this->comentarios = new ComentarioRepository();
    }

    // ================================================================
    // CRIAÇÃO (Diretor)
    // ================================================================

    public function criar(array $dados, int $idDiretor, int $idEscola, string $escolaNome): array
    {
        if (!$this->escolaAtiva($idEscola)) {
            return $this->fail('Sua escola está temporariamente indisponível no sistema. Entre em contato com o suporte da Secretaria de Ensino.');
        }

        $categoria = $dados['categoria'] ?? '';

        if (!in_array($categoria, ['produto', 'servico'], true)) {
            return $this->fail('Categoria inválida. Escolha Produto ou Serviço.');
        }

        // ---- SERVIÇO ----
        if ($categoria === 'servico') {
            $descricao = trim($dados['descricao_servico'] ?? '');
            if (empty($descricao)) {
                return $this->fail('A descrição do serviço é obrigatória.');
            }
            if (strlen($descricao) > App::MAX_DESCRICAO_SERVICO) {
                return $this->fail('A descrição do serviço deve ter no máximo ' . App::MAX_DESCRICAO_SERVICO . ' caracteres.');
            }

            $id = $this->repo->criar([
                'id_escola'         => $idEscola,
                'id_diretor'        => $idDiretor,
                'categoria'         => 'servico',
                'descricao_servico' => $descricao,
            ]);

            $this->notificarAdminDaCategoria('servico', $id, $escolaNome);
            CacheService::invalidarSolicitacoes();
            return $this->ok('Solicitação de serviço criada com sucesso.', ['id' => $id]);
        }

        // ---- PRODUTO ----
        $itens = $dados['itens'] ?? [];
        if (empty($itens) || !is_array($itens)) {
            return $this->fail('Adicione pelo menos um item à solicitação.');
        }
        if (count($itens) > 30) {
            return $this->fail('Limite de 30 itens por solicitação.');
        }

        // Valida cada item
        foreach ($itens as $i => $item) {
            $n = $i + 1;
            if (empty($item['id_produto'])) {
                return $this->fail("Item {$n}: selecione o produto.");
            }
            if (empty($item['id_unidade'])) {
                return $this->fail("Item {$n}: selecione a unidade de medida.");
            }
            $qtd = filter_var($item['quantidade'] ?? 0, FILTER_VALIDATE_FLOAT);
            if ($qtd === false || $qtd <= 0) {
                return $this->fail("Item {$n}: informe uma quantidade válida.");
            }
        }

        $id = $this->repo->criar([
            'id_escola'  => $idEscola,
            'id_diretor' => $idDiretor,
            'categoria'  => 'produto',
        ]);

        foreach ($itens as $item) {
            $this->repo->inserirItem(
                $id,
                (int) $item['id_produto'],
                (int) $item['id_unidade'],
                (float) $item['quantidade']
            );
        }

        $this->notificarAdminDaCategoria('produto', $id, $escolaNome);

        // Registra histórico inicial
        $this->repo->registrarHistorico($id, $idDiretor, null, App::STATUS_PENDENTE, 'Solicitação criada.');
        CacheService::invalidarSolicitacoes();
        return $this->ok('Solicitação de produto criada com sucesso.', ['id' => $id]);
    }

    // ================================================================
    // ATUALIZAÇÃO DE STATUS (Administrador)
    // ================================================================

    public function atualizarStatus(int $idSolicitacao, string $novoStatus, int $idAdmin, string $categoriaAdmin, ?string $observacao = null, ?string $motivoRejeicao = null): array
    {
        $solicitacao = $this->repo->buscarPorId($idSolicitacao);
        if (!$solicitacao) return $this->fail('Solicitação não encontrada.');

        // Admin só pode mexer na sua categoria
        if ($solicitacao['categoria'] !== $categoriaAdmin) {
            return $this->fail('Você não tem permissão para gerenciar esta categoria de solicitação.');
        }

        // Escola inativa: solicitacao congelada (vale tambem para rejeitar)
        if (!$this->escolaAtiva((int) $solicitacao['escola_id'])) {
            return $this->fail('A escola desta solicitação está inativa. A solicitação permanece congelada até a reativação.');
        }

        $statusAtual = $solicitacao['status'];

        // Valida transição
        $transicoesPermitidas = App::TRANSICOES_VALIDAS['administrador'][$statusAtual] ?? [];
        if (!in_array($novoStatus, $transicoesPermitidas, true)) {
            return $this->fail("Não é possível mover de '{$statusAtual}' para '{$novoStatus}'.");
        }

        $extras = ['id_admin' => $idAdmin];
        if ($motivoRejeicao) $extras['motivo_rejeicao'] = $motivoRejeicao;

        $this->repo->atualizarStatus($idSolicitacao, $novoStatus, $extras);
        $this->repo->registrarHistorico($idSolicitacao, $idAdmin, $statusAtual, $novoStatus, $observacao);

        // Notifica diretor (apenas se existir)
        if (!empty($solicitacao['diretor_id'])) {
            $this->notif->statusAtualizado(
                (int) $solicitacao['diretor_id'],
                $idSolicitacao,
                $novoStatus
            );
        }

        CacheService::invalidarSolicitacoes();
        AuditoriaService::solicitacao('status', $idSolicitacao, "Status alterado de {$statusAtual} para {$novoStatus}.");
        return $this->ok('Status atualizado com sucesso.', ['status_novo' => $novoStatus]);
    }

    // ================================================================
    // CANCELAMENTO (Diretor)
    // ================================================================

    public function cancelar(int $idSolicitacao, int $idDiretor, int $idEscola): array
    {
        $solicitacao = $this->repo->buscarPorId($idSolicitacao);
        if (!$solicitacao) return $this->fail('Solicitação não encontrada.');

        // Pertence à escola de quem cancela (diretor, escriturário ou estagiário da mesma escola)
        if ((int) $solicitacao['escola_id'] !== $idEscola) {
            return $this->fail('Você não tem permissão para cancelar esta solicitação.');
        }

        // Escola inativa: solicitacao congelada
        if (!$this->escolaAtiva((int) $solicitacao['escola_id'])) {
            return $this->fail('Sua escola está temporariamente indisponível no sistema. Entre em contato com o suporte da Secretaria de Ensino.');
        }

        // Só pode cancelar se estiver pendente
        if ($solicitacao['status'] !== App::STATUS_PENDENTE) {
            return $this->fail('Só é possível cancelar solicitações com status Pendente.');
        }

        $this->repo->atualizarStatus($idSolicitacao, App::STATUS_CANCELADO, ['cancelado_por' => $idDiretor]);
        AuditoriaService::solicitacao('cancelar', $idSolicitacao, "Solicitacao cancelada pela escola.");
        $this->repo->registrarHistorico($idSolicitacao, $idDiretor, App::STATUS_PENDENTE, App::STATUS_CANCELADO, 'Cancelado pela escola.');

        return $this->ok('Solicitação cancelada com sucesso.');
    }

    // ================================================================
    // REJEIÇÃO (Administrador)
    // ================================================================

    public function rejeitar(int $idSolicitacao, int $idAdmin, string $categoriaAdmin, string $motivo): array
    {
        if (empty(trim($motivo))) {
            return $this->fail('Informe o motivo da rejeição.');
        }
        return $this->atualizarStatus($idSolicitacao, App::STATUS_REJEITADO, $idAdmin, $categoriaAdmin, null, trim($motivo));
    }

    // ================================================================
    // LEITURA (busca)
    // ================================================================

    public function buscarPorId(int $id): ?array
    {
        $solicitacao = $this->repo->buscarPorId($id);
        if (!$solicitacao) return null;

        $solicitacao['itens']           = $this->repo->buscarItens($id);
        $solicitacao['itens_entregues'] = $this->repo->buscarItensEntregues($id);
        $solicitacao['historico']       = $this->repo->buscarHistorico($id);
        $solicitacao['comentarios']     = $this->comentarios->listar($id);
        return $solicitacao;
    }

    public function listarFila(string $categoria, ?int $idEscola = null): array
    {
        return $this->repo->listar([
            'categoria'      => $categoria,
            'excluir_status' => ['concluido', 'cancelado', 'rejeitado'],
            'id_escola'      => $idEscola,
        ]);
    }

    public function listarParaDiretor(int $idEscola): array
    {
        return $this->repo->listar(['id_escola' => $idEscola]);
    }

    public function listarHistorico(array $filtros = []): array
    {
        return $this->repo->listar(array_merge(
            $filtros,
            ['status' => ['concluido', 'cancelado', 'rejeitado']]
        ), 200);
    }

    // ================================================================
    // QR CODE
    // ================================================================

    public function gerarQrUrl(string $token): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? '';
        return "{$baseUrl}/pages/qr-leitura.php?token={$token}";
    }

    // ================================================================
    // Privados
    // ================================================================

    private function notificarAdminDaCategoria(string $categoria, int $idSolicitacao, string $escolaNome): void
    {
        // Busca admin da categoria
        $db = \App\Config\Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id FROM usuarios WHERE perfil = 'administrador' AND categoria = :cat AND ativo = 1 LIMIT 1"
        );
        $stmt->execute([':cat' => $categoria]);
        $adminId = $stmt->fetchColumn();

        if ($adminId) {
            $this->notif->novaSolicitacao((int) $adminId, $idSolicitacao, $escolaNome, $categoria);
        }
    }

    /** Consulta se a escola esta ativa (regra de congelamento de escola inativa). */
    private function escolaAtiva(int $idEscola): bool
    {
        return (new \App\Repositories\EscolaRepository())->estaAtiva($idEscola);
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
