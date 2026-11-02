<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\{App, Database};
use App\Repositories\{EscolaRepository, SolicitacaoRepository};

class RemessaService
{
    private SolicitacaoRepository $repo;

    public function __construct()
    {
        $this->repo = new SolicitacaoRepository();
    }

    private function ok(array $d): array   { return ['success' => true,  'message' => 'OK', 'data' => $d]; }
    private function fail(string $m): array { return ['success' => false, 'message' => $m,  'data' => []]; }

    /**
     * Reúne os dados da Remessa e registra a emissão (enquanto a entrega não foi concluída).
     */
    public function preparar(int $id, string $perfil, ?string $categoriaAdmin, bool $registrar = true): array
    {
        $sol = $this->repo->buscarPorId($id);
        if (!$sol) return $this->fail('Solicitação não encontrada.');

        if ($perfil !== App::PERFIL_SUPERADMIN && $categoriaAdmin !== $sol['categoria']) {
            return $this->fail('Você não tem permissão para imprimir esta Remessa.');
        }

        if (!(new EscolaRepository())->estaAtiva((int) $sol['id_escola'])) {
            return $this->fail('Esta escola está inativa no sistema.');
        }

        $status    = $sol['status'];
        $concluida = $status === App::STATUS_CONCLUIDO;
        $qrValido  = !$concluida && !(int) $sol['qr_invalidado'] && !empty($sol['qr_token'])
                     && in_array($status, [App::STATUS_DESPACHADO, App::STATUS_ITEM_EM_FALTA], true);

        if (!$concluida && !$qrValido) {
            return $this->fail('A Remessa só fica disponível a partir do despacho do pedido.');
        }

        $db = Database::getInstance();
        $st = $db->prepare("SELECT nome, endereco, telefone FROM escolas WHERE id = :id");
        $st->execute([':id' => (int) $sol['id_escola']]);
        $escola = $st->fetch() ?: ['nome' => $sol['escola_nome'], 'endereco' => null, 'telefone' => null];

        $via = (int) $sol['remessa_impressoes'] + 1;
        if ($registrar && $qrValido) {
            $up = $db->prepare("UPDATE solicitacoes SET remessa_impressoes = remessa_impressoes + 1, updated_at = updated_at WHERE id = :id");
            $up->execute([':id' => $id]);
            try {
                AuditoriaService::solicitacao('imprimir_remessa', $id, "Remessa impressa (via {$via}).");
            } catch (\Throwable $e) {
                // auditoria não deve impedir a impressão
            }
        } elseif ($concluida) {
            $via = (int) $sol['remessa_impressoes'];
        }

        $qrUrl = null;
        if ($qrValido) {
            $qrUrl = (new SolicitacaoService())->gerarQrUrl((string) $sol['qr_token']);
        }

        $conclusaoEm = null;
        if ($concluida) {
            foreach (array_reverse($sol_hist = $this->repo->buscarHistorico($id)) as $h) {
                if ($h['status_novo'] === App::STATUS_CONCLUIDO) { $conclusaoEm = $h['created_at']; break; }
            }
        }

        return $this->ok([
            'sol'         => $sol,
            'itens'       => $this->repo->buscarItens($id),
            'entregas' => $this->repo->buscarEntregasAgrupadas($id),
            'itens_entregues' => array_map(
                fn($x) => is_array($x) ? (int) ($x['id_item'] ?? $x['id'] ?? 0) : (int) $x,
                $this->repo->buscarItensEntregues($id)
            ),
            'historico'   => $this->repo->buscarHistorico($id),
            'escola'      => $escola,
            'cfg'         => (new ConfiguracaoService())->obterRemessa(),
            'via'         => $via,
            'concluida'   => $concluida,
            'qr_url'      => $qrUrl,
            'conclusao_em'=> $conclusaoEm,
        ]);
    }
}
