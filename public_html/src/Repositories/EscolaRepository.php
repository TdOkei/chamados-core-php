<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class EscolaRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(bool $apenasAtivas = false): array
    {
        $where = $apenasAtivas ? 'WHERE e.ativo = 1' : '';
        return $this->db->query(
            "SELECT e.id, e.nome, e.codigo, e.endereco, e.telefone, e.ativo, e.created_at,
                    u.nome AS diretor_nome, u.id AS diretor_id
             FROM escolas e
             LEFT JOIN usuarios u ON u.id_escola = e.id AND u.perfil = 'diretor' AND u.ativo = 1
             {$where}
             ORDER BY e.nome"
        )->fetchAll();
    }

    public function estaAtiva(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT ativo FROM escolas WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $ativo = $stmt->fetchColumn();
        return $ativo !== false && (bool) $ativo;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.nome AS diretor_nome, u.id AS diretor_id
             FROM escolas e
             LEFT JOIN usuarios u ON u.id_escola = e.id AND u.perfil = "diretor" AND u.ativo = 1
             WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function codigoExiste(string $codigo, ?int $ignorarId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM escolas WHERE codigo = :codigo';
        $params = [':codigo' => $codigo];
        if ($ignorarId) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignorarId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO escolas (nome, codigo, endereco, telefone)
             VALUES (:nome, :codigo, :endereco, :telefone)'
        );
        $stmt->execute([
            ':nome'     => $dados['nome'],
            ':codigo'   => $dados['codigo'],
            ':endereco' => $dados['endereco'] ?? null,
            ':telefone' => $dados['telefone'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->db->prepare(
            'UPDATE escolas
             SET nome = :nome, codigo = :codigo, endereco = :endereco, telefone = :telefone
             WHERE id = :id'
        );
        $stmt->execute([
            ':nome'     => $dados['nome'],
            ':codigo'   => $dados['codigo'],
            ':endereco' => $dados['endereco'] ?? null,
            ':telefone' => $dados['telefone'] ?? null,
            ':id'       => $id,
        ]);
    }

    public function alternarAtivo(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE escolas SET ativo = NOT ativo WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $stmt2 = $this->db->prepare('SELECT ativo FROM escolas WHERE id = :id');
        $stmt2->execute([':id' => $id]);
        return (bool) $stmt2->fetchColumn();
    }

    public function possuiSolicitacoesAtivas(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM solicitacoes
             WHERE id_escola = :id AND status NOT IN ('concluido','cancelado','rejeitado')"
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
