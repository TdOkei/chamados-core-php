<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class OportunidadeRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------------
    // Oportunidades
    // ----------------------------------------------------------------

    public function listar(?int $idAdmin = null, ?string $status = null): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($idAdmin) {
            $where[] = 'o.id_admin = :admin';
            $params[':admin'] = $idAdmin;
        }
        if ($status) {
            $where[] = 'o.status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->db->prepare(
            "SELECT o.id, o.titulo, o.descricao, o.pedir_quantidade, o.id_unidade_quantidade, o.categoria, o.status, o.editavel, o.created_at,
                    u.nome AS admin_nome,
                    COUNT(oe.id) AS total_escolas,
                    SUM(CASE WHEN oe.decisao='aceita'    THEN 1 ELSE 0 END) AS aceitas,
                    SUM(CASE WHEN oe.decisao='rejeitada' THEN 1 ELSE 0 END) AS rejeitadas,
                    SUM(CASE WHEN oe.decisao='pendente'  THEN 1 ELSE 0 END) AS pendentes
             FROM oportunidades o
             JOIN usuarios u ON u.id = o.id_admin
             LEFT JOIN oportunidade_escolas oe ON oe.id_oportunidade = o.id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, u.nome AS admin_nome
             FROM oportunidades o
             JOIN usuarios u ON u.id = o.id_admin
             WHERE o.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO oportunidades (id_admin, titulo, descricao, pedir_quantidade, id_unidade_quantidade, categoria)
             VALUES (:admin, :titulo, :desc, :pedir, :unidade, :cat)"
        );
        $stmt->execute([
            ':admin'   => $dados['id_admin'],
            ':titulo'  => $dados['titulo'],
            ':desc'    => $dados['descricao'],
            ':pedir'   => $dados['pedir_quantidade'] ?? 0,
            ':unidade' => $dados['id_unidade_quantidade'] ?? null,
            ':cat'     => $dados['categoria'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->db->prepare(
            "UPDATE oportunidades SET titulo = :titulo, descricao = :desc, pedir_quantidade = :pedir, id_unidade_quantidade = :unidade, categoria = :cat WHERE id = :id"
        );
        $stmt->execute([
            ':titulo'  => $dados['titulo'],
            ':desc'    => $dados['descricao'],
            ':pedir'   => $dados['pedir_quantidade'] ?? 0,
            ':unidade' => $dados['id_unidade_quantidade'] ?? null,
            ':cat'     => $dados['categoria'],
            ':id'      => $id,
        ]);
    }

    public function alterarStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE oportunidades SET status = :s WHERE id = :id");
        $stmt->execute([':s' => $status, ':id' => $id]);
    }

    public function marcarNaoEditavel(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE oportunidades SET editavel = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function temAlgumaDecisao(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM oportunidade_escolas
             WHERE id_oportunidade = :id AND decisao != 'pendente'"
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ----------------------------------------------------------------
    // Itens da oportunidade
    // ----------------------------------------------------------------

    public function buscarItens(int $idOportunidade): array
    {
        $stmt = $this->db->prepare(
            "SELECT oi.*, p.nome AS produto_nome, u.sigla AS unidade_sigla, u.nome AS unidade_nome
             FROM oportunidade_itens oi
             JOIN produtos        p ON p.id = oi.id_produto
             JOIN unidades_medida u ON u.id = oi.id_unidade
             WHERE oi.id_oportunidade = :id"
        );
        $stmt->execute([':id' => $idOportunidade]);
        return $stmt->fetchAll();
    }

    public function inserirItem(int $idOportunidade, int $idProduto, int $idUnidade, float $quantidade): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO oportunidade_itens (id_oportunidade, id_produto, id_unidade, quantidade)
             VALUES (:op, :prod, :un, :qtd)"
        );
        $stmt->execute([':op' => $idOportunidade, ':prod' => $idProduto, ':un' => $idUnidade, ':qtd' => $quantidade]);
    }

    public function removerItens(int $idOportunidade): void
    {
        $this->db->prepare("DELETE FROM oportunidade_itens WHERE id_oportunidade = :id")->execute([':id' => $idOportunidade]);
    }

    // ----------------------------------------------------------------
    // Escolas vinculadas
    // ----------------------------------------------------------------

    public function vincularEscolas(int $idOportunidade, array $idEscolas): void
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO oportunidade_escolas (id_oportunidade, id_escola) VALUES (:op, :esc)"
        );
        foreach ($idEscolas as $idEscola) {
            $stmt->execute([':op' => $idOportunidade, ':esc' => (int) $idEscola]);
        }
    }

    public function buscarEscolasVinculadas(int $idOportunidade): array
    {
        $stmt = $this->db->prepare(
            "SELECT oe.id, oe.id_escola, oe.decisao, oe.id_solicitacao, oe.decidido_em,
                    e.nome AS escola_nome, u.nome AS diretor_nome
             FROM oportunidade_escolas oe
             JOIN escolas e ON e.id = oe.id_escola
             LEFT JOIN usuarios u ON u.id_escola = e.id AND u.perfil = 'diretor'
             WHERE oe.id_oportunidade = :id
             ORDER BY e.nome"
        );
        $stmt->execute([':id' => $idOportunidade]);
        return $stmt->fetchAll();
    }

    /**
     * Busca oportunidades visíveis para uma escola específica (status ativa, decisão pendente).
     */
    public function listarParaEscola(int $idEscola): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.id, o.titulo, o.descricao, o.pedir_quantidade, o.id_unidade_quantidade, o.categoria, o.created_at,
                    oe.decisao, oe.id AS oe_id,
                    u.nome AS admin_nome,
                    um.sigla AS unidade_sigla, um.nome AS unidade_nome
             FROM oportunidade_escolas oe
             JOIN oportunidades o  ON o.id = oe.id_oportunidade
             JOIN usuarios     u  ON u.id = o.id_admin
             LEFT JOIN unidades_medida um ON um.id = o.id_unidade_quantidade
             WHERE oe.id_escola = :esc
               AND o.status = 'ativa'
               AND oe.decisao = 'pendente'
               AND oe.id_escola IN (SELECT id FROM escolas WHERE ativo = 1)
             ORDER BY o.created_at DESC"
        );
        $stmt->execute([':esc' => $idEscola]);
        return $stmt->fetchAll();
    }

    public function buscarDecisaoEscola(int $idOportunidade, int $idEscola): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM oportunidade_escolas
             WHERE id_oportunidade = :op AND id_escola = :esc LIMIT 1"
        );
        $stmt->execute([':op' => $idOportunidade, ':esc' => $idEscola]);
        return $stmt->fetch() ?: null;
    }

    public function registrarDecisao(int $idOportunidade, int $idEscola, string $decisao, ?int $idSolicitacao = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE oportunidade_escolas
             SET decisao = :dec, id_solicitacao = :sol, decidido_em = NOW()
             WHERE id_oportunidade = :op AND id_escola = :esc"
        );
        $stmt->execute([
            ':dec'  => $decisao,
            ':sol'  => $idSolicitacao,
            ':op'   => $idOportunidade,
            ':esc'  => $idEscola,
        ]);
    }

    /**
     * Busca diretores das escolas vinculadas para enviar notificações.
     */
    public function buscarDiretoresDasEscolas(int $idOportunidade): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id FROM usuarios u
             JOIN oportunidade_escolas oe ON oe.id_escola = u.id_escola
             WHERE oe.id_oportunidade = :op AND u.perfil IN ('diretor','escriturario') AND u.ativo = 1
               AND oe.id_escola IN (SELECT id FROM escolas WHERE ativo = 1)"
        );
        $stmt->execute([':op' => $idOportunidade]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function buscarHistoricoEscola(int $idEscola): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.id, o.titulo, o.categoria, oe.decisao, oe.decidido_em, o.created_at
             FROM oportunidade_escolas oe
             JOIN oportunidades o ON o.id = oe.id_oportunidade
             WHERE oe.id_escola = :esc AND oe.decisao != 'pendente'
             ORDER BY oe.decidido_em DESC"
        );
        $stmt->execute([':esc' => $idEscola]);
        return $stmt->fetchAll();
    }
}
