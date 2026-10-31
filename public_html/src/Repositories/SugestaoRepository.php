<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class SugestaoRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(?string $status = null): array
    {
        $where  = $status ? 'WHERE sp.status = :status' : '';
        $params = $status ? [':status' => $status] : [];

        $stmt = $this->db->prepare(
            "SELECT sp.id, sp.descricao, sp.link, sp.status, sp.created_at,
                    e.nome AS escola_nome, u.nome AS diretor_nome
             FROM sugestoes_produto sp
             JOIN escolas  e ON e.id = sp.id_escola
             JOIN usuarios u ON u.id = sp.id_diretor
             {$where}
             ORDER BY sp.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT sp.*, e.nome AS escola_nome, u.nome AS diretor_nome
             FROM sugestoes_produto sp
             JOIN escolas  e ON e.id = sp.id_escola
             JOIN usuarios u ON u.id = sp.id_diretor
             WHERE sp.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(int $idEscola, int $idDiretor, string $descricao, ?string $link = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sugestoes_produto (id_escola, id_diretor, descricao, link)
             VALUES (:escola, :diretor, :desc, :link)'
        );
        $stmt->execute([':escola' => $idEscola, ':diretor' => $idDiretor, ':desc' => $descricao, ':link' => $link ?: null]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizarStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE sugestoes_produto SET status = :s WHERE id = :id');
        $stmt->execute([':s' => $status, ':id' => $id]);
    }

    public function contarPendentes(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM sugestoes_produto WHERE status = 'pendente'"
        )->fetchColumn();
    }
}
