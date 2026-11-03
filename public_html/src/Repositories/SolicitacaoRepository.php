<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class SolicitacaoRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------------
    // Listagem
    // ----------------------------------------------------------------

    /**
     * Lista solicitações com filtros opcionais.
     */
    public function listar(array $filtros = [], int $limite = 100, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['categoria'])) {
            $where[] = 's.categoria = :categoria';
            $params[':categoria'] = $filtros['categoria'];
        }
        if (!empty($filtros['status'])) {
            if (is_array($filtros['status'])) {
                $placeholders = [];
                foreach ($filtros['status'] as $i => $st) {
                    $placeholders[] = ":st{$i}";
                    $params[":st{$i}"] = $st;
                }
                $where[] = 's.status IN (' . implode(',', $placeholders) . ')';
            } else {
                $where[] = 's.status = :status';
                $params[':status'] = $filtros['status'];
            }
        }
        if (!empty($filtros['id_escola'])) {
            $where[] = 's.id_escola = :id_escola';
            $params[':id_escola'] = $filtros['id_escola'];
        }
        if (!empty($filtros['id_diretor'])) {
            $where[] = 's.id_diretor = :id_diretor';
            $params[':id_diretor'] = $filtros['id_diretor'];
        }
        if (isset($filtros['excluir_status']) && is_array($filtros['excluir_status'])) {
            $placeholders = [];
            foreach ($filtros['excluir_status'] as $i => $st) {
                $placeholders[] = ":est{$i}";
                $params[":est{$i}"] = $st;
            }
            $where[] = 's.status NOT IN (' . implode(',', $placeholders) . ')';
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare(
            "SELECT s.id, s.categoria, s.status, s.descricao_servico, s.foto_servico,
                    s.qr_token, s.qr_invalidado, s.nome_recebedor,
                    s.obs_item_falta, s.motivo_rejeicao, s.created_at, s.updated_at,
                    e.nome AS escola_nome, e.id AS escola_id,
                    u.nome AS diretor_nome, u.id AS diretor_id,
                    a.nome AS admin_nome
             FROM solicitacoes s
             JOIN escolas  e ON e.id = s.id_escola
             JOIN usuarios u ON u.id = s.id_diretor
             LEFT JOIN usuarios a ON a.id = s.id_admin
             WHERE {$whereStr}
             ORDER BY s.created_at ASC
             LIMIT :lim OFFSET :off"
        );

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*,
                    e.nome AS escola_nome, e.id AS escola_id,
                    u.nome AS diretor_nome,
                    a.nome AS admin_nome, a.id AS admin_id
             FROM solicitacoes s
             JOIN escolas  e ON e.id = s.id_escola
             JOIN usuarios u ON u.id = s.id_diretor
             LEFT JOIN usuarios a ON a.id = s.id_admin
             WHERE s.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, e.nome AS escola_nome, e.id AS escola_id,
                    u.nome AS diretor_nome, u.id AS diretor_id
             FROM solicitacoes s
             JOIN escolas  e ON e.id = s.id_escola
             JOIN usuarios u ON u.id = s.id_diretor
             WHERE s.qr_token = :token AND s.qr_invalidado = 0
             LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        return $stmt->fetch() ?: null;
    }

    // ----------------------------------------------------------------
    // Itens
    // ----------------------------------------------------------------

    public function buscarItens(int $idSolicitacao): array
    {
        $stmt = $this->db->prepare(
            "SELECT si.id, si.quantidade, si.detalhes,
                    p.nome AS produto_nome, p.id AS produto_id,
                    u.nome AS unidade_nome, u.sigla AS unidade_sigla
             FROM solicitacao_itens si
             JOIN produtos       p ON p.id = si.id_produto
             JOIN unidades_medida u ON u.id = si.id_unidade
             WHERE si.id_solicitacao = :id"
        );
        $stmt->execute([':id' => $idSolicitacao]);
        return $stmt->fetchAll();
    }

    public function inserirItem(int $idSolicitacao, int $idProduto, int $idUnidade, float $quantidade, ?string $detalhes = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO solicitacao_itens (id_solicitacao, id_produto, id_unidade, quantidade, detalhes)
             VALUES (:sol, :prod, :un, :qtd, :det)'
        );
        $stmt->execute([
            ':sol'  => $idSolicitacao,
            ':prod' => $idProduto,
            ':un'   => $idUnidade,
            ':qtd'  => $quantidade,
            ':det'  => $detalhes,
        ]);
    }

    // ----------------------------------------------------------------
    // Criação e atualização
    // ----------------------------------------------------------------

    public function criar(array $dados): int
    {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->db->prepare(
            "INSERT INTO solicitacoes
                (id_escola, id_diretor, categoria, descricao_servico, qr_token, status)
             VALUES
                (:escola, :diretor, :categoria, :descricao, :token, 'pendente')"
        );
        $stmt->execute([
            ':escola'    => $dados['id_escola'],
            ':diretor'   => $dados['id_diretor'],
            ':categoria' => $dados['categoria'],
            ':descricao' => $dados['descricao_servico'] ?? null,
            ':token'     => $token,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizarStatus(int $id, string $novoStatus, array $extras = []): void
    {
        $campos = ['status = :status', 'updated_at = NOW()'];
        $params = [':status' => $novoStatus, ':id' => $id];

        if (isset($extras['id_admin'])) {
            $campos[] = 'id_admin = :admin';
            $params[':admin'] = $extras['id_admin'];
        }
        if (isset($extras['nome_recebedor'])) {
            $campos[] = 'nome_recebedor = :recebedor';
            $params[':recebedor'] = $extras['nome_recebedor'];
        }
        if (isset($extras['obs_item_falta'])) {
            $campos[] = 'obs_item_falta = :obs_falta';
            $params[':obs_falta'] = $extras['obs_item_falta'];
        }
        if (isset($extras['motivo_rejeicao'])) {
            $campos[] = 'motivo_rejeicao = :motivo';
            $params[':motivo'] = $extras['motivo_rejeicao'];
        }
        if (isset($extras['cancelado_por'])) {
            $campos[] = 'cancelado_por = :cancelado_por';
            $params[':cancelado_por'] = $extras['cancelado_por'];
        }
        if (isset($extras['foto_conclusao'])) {
            $campos[] = 'foto_conclusao = :foto_conc';
            $params[':foto_conc'] = $extras['foto_conclusao'];
        }
        if (isset($extras['qr_invalidado'])) {
            $campos[] = 'qr_invalidado = :qr_inv';
            $params[':qr_inv'] = $extras['qr_invalidado'] ? 1 : 0;
        }

        $stmt = $this->db->prepare(
            'UPDATE solicitacoes SET ' . implode(', ', $campos) . ' WHERE id = :id'
        );
        $stmt->execute($params);
    }

    // ----------------------------------------------------------------
    // Histórico de status
    // ----------------------------------------------------------------

    public function salvarFotoConclusao(int $id, string $arquivo): void
    {
        $stmt = $this->db->prepare('UPDATE solicitacoes SET foto_conclusao = :f, updated_at = updated_at WHERE id = :id');
        $stmt->execute([':f' => $arquivo, ':id' => $id]);
    }

    /** Entregas agrupadas por viagem (leitura do QR): data, quem recebeu e itens. */
    public function buscarEntregasAgrupadas(int $idSolicitacao): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id_log_qrcode, si.quantidade, si.detalhes, p.nome AS produto_nome, u.sigla AS unidade_sigla,
                    l.created_at AS entregue_em, l.observacao AS log_obs
             FROM solicitacao_itens_entregues e
             JOIN solicitacao_itens si ON si.id = e.id_item
             JOIN produtos p ON p.id = si.id_produto
             JOIN unidades_medida u ON u.id = si.id_unidade
             LEFT JOIN log_qrcode l ON l.id = e.id_log_qrcode
             WHERE e.id_solicitacao = :sol
             ORDER BY l.created_at ASC, e.id_log_qrcode ASC, p.nome ASC'
        );
        $stmt->execute([':sol' => $idSolicitacao]);

        $viagens = [];
        foreach ($stmt->fetchAll() as $r) {
            $k = (string) ($r['id_log_qrcode'] ?? 'sem-registro');
            if (!isset($viagens[$k])) {
                $obs = (string) ($r['log_obs'] ?? '');
                $rec = $obs;
                if (preg_match('/^Recebido por: (.*?)(?:\. Itens (?:entregues|faltantes):|$)/su', $obs, $m)) {
                    $rec = $m[1];
                }
                $viagens[$k] = ['quando' => $r['entregue_em'], 'recebedor' => trim($rec), 'itens' => []];
            }
            $viagens[$k]['itens'][] = [
                'nome' => $r['produto_nome'],
                'detalhes' => $r['detalhes'],
                'qtd'  => (float) $r['quantidade'],
                'un'   => $r['unidade_sigla'],
            ];
        }
        return array_values($viagens);
    }

    public function registrarHistorico(int $idSolicitacao, int $idUsuario, ?string $statusAnterior, string $statusNovo, ?string $obs = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO historico_status (id_solicitacao, id_usuario, status_anterior, status_novo, observacao)
             VALUES (:sol, :uid, :ant, :novo, :obs)'
        );
        $stmt->execute([
            ':sol'  => $idSolicitacao,
            ':uid'  => $idUsuario,
            ':ant'  => $statusAnterior,
            ':novo' => $statusNovo,
            ':obs'  => $obs,
        ]);
    }

    public function buscarHistorico(int $idSolicitacao): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*, u.nome AS usuario_nome, u.perfil AS usuario_perfil
             FROM historico_status h
             JOIN usuarios u ON u.id = h.id_usuario
             WHERE h.id_solicitacao = :id
             ORDER BY h.created_at ASC"
        );
        $stmt->execute([':id' => $idSolicitacao]);
        return $stmt->fetchAll();
    }

    // ----------------------------------------------------------------
    // Log de QR Code
    // ----------------------------------------------------------------

    public function registrarItemEntregue(int $idSolicitacao, int $idItem, ?int $idLogQrcode): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO solicitacao_itens_entregues (id_solicitacao, id_item, id_log_qrcode)
             VALUES (:sol, :item, :log)'
        );
        $stmt->execute([
            ':sol'  => $idSolicitacao,
            ':item' => $idItem,
            ':log'  => $idLogQrcode,
        ]);
    }

    public function ultimoLogQrId(): ?int
    {
        $id = $this->db->lastInsertId();
        return $id ? (int) $id : null;
    }

    public function buscarItensEntregues(int $idSolicitacao): array
    {
        $stmt = $this->db->prepare(
            'SELECT id_item FROM solicitacao_itens_entregues WHERE id_solicitacao = :sol'
        );
        $stmt->execute([':sol' => $idSolicitacao]);
        return array_column($stmt->fetchAll(), 'id_item');
    }

    public function registrarLogQr(int $idSolicitacao, int $idDiretor, string $token, string $resultado, string $ip, string $ua, ?string $obs = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO log_qrcode (id_solicitacao, id_diretor, token_usado, resultado, ip, user_agent, observacao)
             VALUES (:sol, :dir, :token, :res, :ip, :ua, :obs)'
        );
        $stmt->execute([
            ':sol'   => $idSolicitacao,
            ':dir'   => $idDiretor,
            ':token' => $token,
            ':res'   => $resultado,
            ':ip'    => $ip,
            ':ua'    => $ua,
            ':obs'   => $obs,
        ]);
    }

    // ----------------------------------------------------------------
    // Contagens para dashboard e badges
    // ----------------------------------------------------------------

    public function contarPorStatus(string $categoria, ?int $idEscola = null): array
    {
        $where  = 'WHERE categoria = :cat';
        $params = [':cat' => $categoria];
        if ($idEscola) {
            $where .= ' AND id_escola = :esc';
            $params[':esc'] = $idEscola;
        }
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) as total FROM solicitacoes {$where} GROUP BY status"
        );
        $stmt->execute($params);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['total'];
        }
        return $result;
    }

    public function contarPendentes(string $categoria): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM solicitacoes WHERE categoria = :cat AND status = 'pendente'"
        );
        $stmt->execute([':cat' => $categoria]);
        return (int) $stmt->fetchColumn();
    }
}
