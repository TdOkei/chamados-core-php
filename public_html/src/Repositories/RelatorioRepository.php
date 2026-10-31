<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class RelatorioRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Totais gerais do sistema. */

    private function montarWhere(array $filtros): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $where[]                  = "DATE(s.created_at) >= :data_inicio";
            $params[':data_inicio']   = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[]                = "DATE(s.created_at) <= :data_fim";
            $params[':data_fim']    = $filtros['data_fim'];
        }
        if (!empty($filtros['id_escola'])) {
            $where[]                = "s.id_escola = :id_escola";
            $params[':id_escola']   = $filtros['id_escola'];
        }

        $sql = $where ? "AND " . implode(" AND ", $where) : "";
        return [$sql, $params];
    }

    public function totaisGerais(array $filtros = []): array
    {
        $row = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM escolas   WHERE ativo = 1)                        AS escolas_ativas,
                (SELECT COUNT(*) FROM usuarios  WHERE ativo = 1 AND perfil != 'superadmin') AS usuarios_ativos,
                (SELECT COUNT(*) FROM solicitacoes)                                      AS total_solicitacoes,
                (SELECT COUNT(*) FROM solicitacoes WHERE status = 'pendente')            AS pendentes,
                (SELECT COUNT(*) FROM solicitacoes WHERE status = 'concluido')           AS concluidas,
                (SELECT COUNT(*) FROM solicitacoes WHERE status = 'cancelado')           AS canceladas,
                (SELECT COUNT(*) FROM solicitacoes WHERE status = 'rejeitado')           AS rejeitadas,
                (SELECT COUNT(*) FROM solicitacoes WHERE status = 'item_em_falta')       AS item_em_falta,
                (SELECT COUNT(*) FROM solicitacoes WHERE status = 'retornado')           AS retornadas,
                (SELECT COUNT(*) FROM sugestoes_produto WHERE status = 'pendente')       AS sugestoes_pendentes,
                (SELECT COUNT(*) FROM oportunidades WHERE status = 'ativa')              AS oportunidades_ativas"
        )->fetch();
        return $row;
    }

    /** Solicitações por escola (ranking). */
    public function solicitacoesPorEscola(array $filtros = []): array
    {
        [$where, $params] = $this->montarWhere($filtros);
        $stmt = $this->db->prepare(
            "SELECT e.nome AS escola,
                    COUNT(s.id)                                    AS total,
                    SUM(s.status = 'concluido')                    AS concluidas,
                    SUM(s.status IN ('pendente','em_analise','em_processamento','despachado')) AS em_aberto,
                    SUM(s.status = 'cancelado')                    AS canceladas,
                    SUM(s.status = 'rejeitado')                    AS rejeitadas
             FROM escolas e
             LEFT JOIN solicitacoes s ON s.id_escola = e.id
             WHERE e.ativo = 1 {$where}
             GROUP BY e.id, e.nome
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Solicitações por mês (últimos 12 meses). */
    public function solicitacoesPorMes(array $filtros = []): array
    {
        [$where, $params] = $this->montarWhere($filtros);
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes,
                    DATE_FORMAT(created_at, '%b/%Y')  AS mes_label,
                    COUNT(*)                           AS total,
                    SUM(status = 'concluido')          AS concluidas
             FROM solicitacoes
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) {$where}
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY mes ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Distribuição por status (para gráfico de pizza). */
    public function distribuicaoPorStatus(array $filtros = []): array
    {
        [$where, $params] = $this->montarWhere($filtros);
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS total
             FROM solicitacoes
             WHERE 1=1 {$where}
             GROUP BY status
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Distribuição por categoria. */
    public function distribuicaoPorCategoria(array $filtros = []): array
    {
        [$where, $params] = $this->montarWhere($filtros);
        $stmt = $this->db->prepare(
            "SELECT categoria, COUNT(*) AS total
             FROM solicitacoes
             WHERE 1=1 {$where}
             GROUP BY categoria"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Top produtos mais solicitados. */
    public function topProdutos(int $limite = 10, array $filtros = []): array
    {
        [$where, $params] = $this->montarWhere($filtros);
        $stmt = $this->db->prepare(
            "SELECT p.nome AS produto,
                    COUNT(si.id)        AS vezes_solicitado,
                    SUM(si.quantidade)  AS quantidade_total,
                    u.sigla             AS unidade
             FROM solicitacao_itens si
             JOIN solicitacoes     s ON s.id = si.id_solicitacao
             JOIN produtos         p ON p.id = si.id_produto
             JOIN unidades_medida  u ON u.id = si.id_unidade
             WHERE 1=1 {$where}
             GROUP BY p.id, p.nome, u.sigla
             ORDER BY vezes_solicitado DESC
             LIMIT {$limite}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Tempo médio de conclusão por escola (em dias). */
    public function tempoMedioConclusao(array $filtros = []): array
    {
        [$where, $params] = $this->montarWhere($filtros);
        $stmt = $this->db->prepare(
            "SELECT e.nome AS escola,
                    COUNT(s.id)                                      AS concluidas,
                    ROUND(AVG(DATEDIFF(s.updated_at, s.created_at)),1) AS dias_media
             FROM solicitacoes s
             JOIN escolas e ON e.id = s.id_escola
             WHERE s.status = 'concluido' {$where}
             GROUP BY e.id, e.nome
             ORDER BY dias_media ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Log de QR Code — totais por resultado. */
    public function totaisQr(array $filtros = []): array
    {
        [$where, $params] = $this->montarWhere($filtros);
        $stmt = $this->db->prepare(
            "SELECT resultado, COUNT(*) AS total
             FROM log_qrcode
             GROUP BY resultado
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Usuários bloqueados atualmente. */
    public function itensFaltantesRecorrentes(int $limite = 10, array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['escola'])) {
            $where[]           = 's.id_escola = :escola';
            $params[':escola'] = $filtros['escola'];
        }
        if (!empty($filtros['de'])) {
            $where[]       = 'sie.created_at >= :de';
            $params[':de'] = $filtros['de'] . ' 00:00:00';
        }
        if (!empty($filtros['ate'])) {
            $where[]        = 'sie.created_at <= :ate';
            $params[':ate'] = $filtros['ate'] . ' 23:59:59';
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.nome                          AS produto_nome,
                p.categoria                     AS produto_categoria,
                COUNT(sie.id)                   AS total_faltantes,
                COUNT(DISTINCT sie.id_solicitacao) AS total_solicitacoes
            FROM solicitacao_itens_entregues sie
            JOIN solicitacao_itens si ON si.id = sie.id_item
            JOIN produtos p           ON p.id  = si.id_produto
            JOIN solicitacoes s        ON s.id  = sie.id_solicitacao
            {$whereStr}
            GROUP BY p.id, p.nome, p.categoria
            ORDER BY total_faltantes DESC
            LIMIT {$limite}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function usuariosBloqueados(): array
    {
        return $this->db->query(
            "SELECT u.nome, u.username, u.email, u.perfil,
                    e.nome AS escola_nome, u.tentativas_login
             FROM usuarios u
             LEFT JOIN escolas e ON e.id = u.id_escola
             WHERE u.bloqueado = 1
             ORDER BY u.nome"
        )->fetchAll();
    }
}
