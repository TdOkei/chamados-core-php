<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class HistoricoRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lista solicitações do histórico (concluídas, canceladas, rejeitadas)
     * com todos os filtros da tela.
     */
    public function listar(array $filtros = [], int $limite = 500): array
    {
        $where  = ["s.status IN ('concluido','cancelado','rejeitado')"];
        $params = [];

        if (!empty($filtros['status'])) {
            $where[] = 's.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['categoria'])) {
            $where[] = 's.categoria = :categoria';
            $params[':categoria'] = $filtros['categoria'];
        }
        if (!empty($filtros['id_escola'])) {
            $where[] = 's.id_escola = :id_escola';
            $params[':id_escola'] = $filtros['id_escola'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'DATE(s.created_at) >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'DATE(s.created_at) <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'];
        }
        if (!empty($filtros['busca'])) {
            $where[] = '(e.nome LIKE :busca OR CAST(s.id AS CHAR) LIKE :busca)';
            $params[':busca'] = '%' . $filtros['busca'] . '%';
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare(
            "SELECT s.id, s.categoria, s.status, s.descricao_servico,
                    s.nome_recebedor, s.obs_item_falta, s.motivo_rejeicao,
                    s.created_at, s.updated_at,
                    e.nome  AS escola_nome,
                    u.nome  AS diretor_nome,
                    a.nome  AS admin_nome
             FROM solicitacoes s
             JOIN escolas  e ON e.id = s.id_escola
             JOIN usuarios u ON u.id = s.id_diretor
             LEFT JOIN usuarios a ON a.id = s.id_admin
             WHERE {$whereStr}
             ORDER BY s.updated_at DESC
             LIMIT :lim"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca itens de uma solicitação para incluir no export.
     */
    public function buscarItensSolicitacao(int $idSolicitacao): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.nome AS produto, si.quantidade, u.sigla AS unidade
             FROM solicitacao_itens si
             JOIN produtos       p ON p.id = si.id_produto
             JOIN unidades_medida u ON u.id = si.id_unidade
             WHERE si.id_solicitacao = :id"
        );
        $stmt->execute([':id' => $idSolicitacao]);
        return $stmt->fetchAll();
    }

    /**
     * Totalizadores para o cabeçalho da tela de histórico.
     */
    public function totais(array $filtros = []): array
    {
        $where  = ["s.status IN ('concluido','cancelado','rejeitado')"];
        $params = [];

        if (!empty($filtros['id_escola'])) {
            $where[] = 's.id_escola = :id_escola';
            $params[':id_escola'] = $filtros['id_escola'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'DATE(s.created_at) >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'DATE(s.created_at) <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'];
        }

        $whereStr = implode(' AND ', $where);
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(s.status = 'concluido')  AS concluidas,
                SUM(s.status = 'cancelado')  AS canceladas,
                SUM(s.status = 'rejeitado')  AS rejeitadas
             FROM solicitacoes s
             JOIN escolas e ON e.id = s.id_escola
             WHERE {$whereStr}"
        );
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Lista de escolas com solicitações no histórico (para filtro).
     */
    public function listarEscolas(): array
    {
        return $this->db->query(
            "SELECT DISTINCT e.id, e.nome
             FROM solicitacoes s
             JOIN escolas e ON e.id = s.id_escola
             WHERE s.status IN ('concluido','cancelado','rejeitado')
             ORDER BY e.nome"
        )->fetchAll();
    }

    /**
     * Busca log de QR Code para auditoria.
     */
    public function listarLogQr(array $filtros = [], int $limite = 300): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['id_solicitacao'])) {
            $where[] = 'lq.id_solicitacao = :sol';
            $params[':sol'] = $filtros['id_solicitacao'];
        }
        if (!empty($filtros['resultado'])) {
            $where[] = 'lq.resultado = :res';
            $params[':res'] = $filtros['resultado'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'DATE(lq.created_at) >= :di';
            $params[':di'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'DATE(lq.created_at) <= :df';
            $params[':df'] = $filtros['data_fim'];
        }

        $whereStr = implode(' AND ', $where);
        $stmt = $this->db->prepare(
            "SELECT lq.id, lq.id_solicitacao, lq.resultado, lq.ip,
                    lq.observacao, lq.created_at,
                    u.nome AS diretor_nome,
                    e.nome AS escola_nome
             FROM log_qrcode lq
             JOIN usuarios u ON u.id = lq.id_diretor
             LEFT JOIN solicitacoes s ON s.id = lq.id_solicitacao
             LEFT JOIN escolas e ON e.id = s.id_escola
             WHERE {$whereStr}
             ORDER BY lq.created_at DESC
             LIMIT :lim"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
