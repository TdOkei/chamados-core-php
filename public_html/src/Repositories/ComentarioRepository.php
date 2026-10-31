<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class ComentarioRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(int $idSolicitacao): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.nome AS usuario_nome
             FROM solicitacao_comentarios c
             JOIN usuarios u ON u.id = c.id_usuario
             WHERE c.id_solicitacao = :id
             ORDER BY c.created_at ASC"
        );
        $stmt->execute([':id' => $idSolicitacao]);
        return $stmt->fetchAll();
    }

    public function criar(int $idSolicitacao, int $idUsuario, string $perfil, string $mensagem): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO solicitacao_comentarios (id_solicitacao, id_usuario, perfil, mensagem)
             VALUES (:sol, :uid, :perfil, :msg)"
        );
        $stmt->execute([
            ':sol'   => $idSolicitacao,
            ':uid'   => $idUsuario,
            ':perfil'=> $perfil,
            ':msg'   => $mensagem,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.nome AS usuario_nome
             FROM solicitacao_comentarios c
             JOIN usuarios u ON u.id = c.id_usuario
             WHERE c.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
