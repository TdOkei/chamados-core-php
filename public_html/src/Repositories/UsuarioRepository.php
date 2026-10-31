<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class UsuarioRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(?string $perfil = null, ?int $idEscola = null): array
    {
        $where  = ['u.perfil != "superadmin"'];
        $params = [];

        if ($perfil) {
            $where[]            = 'u.perfil = :perfil';
            $params[':perfil']  = $perfil;
        }
        if ($idEscola) {
            $where[]             = 'u.id_escola = :escola';
            $params[':escola']   = $idEscola;
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);

        $stmt = $this->db->prepare(
            "SELECT u.id, u.nome, u.username, u.email, u.perfil, u.categoria,
                    u.ativo, u.bloqueado, u.tentativas_login, u.ultimo_login, u.created_at,
                    e.nome AS escola_nome, e.id AS escola_id
             FROM usuarios u
             LEFT JOIN escolas e ON e.id = u.id_escola
             {$whereStr}
             ORDER BY u.nome"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.nome, u.username, u.email, u.perfil, u.categoria,
                    u.id_escola, u.ativo, u.bloqueado, u.tentativas_login,
                    u.ultimo_login, u.created_at,
                    e.nome AS escola_nome
             FROM usuarios u
             LEFT JOIN escolas e ON e.id = u.id_escola
             WHERE u.id = :id AND u.perfil != "superadmin"
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function usernameExiste(string $username, ?int $ignorarId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM usuarios WHERE username = :u';
        $params = [':u' => $username];
        if ($ignorarId) { $sql .= ' AND id != :id'; $params[':id'] = $ignorarId; }
        return (int) $this->db->prepare($sql)->execute($params) && (int) $this->db->prepare($sql)->execute($params) > 0;
    }

    public function emailExiste(string $email, ?int $ignorarId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM usuarios WHERE email = :e';
        $params = [':e' => $email];
        if ($ignorarId) { $sql .= ' AND id != :id'; $params[':id'] = $ignorarId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function usernameExisteVerify(string $username, ?int $ignorarId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM usuarios WHERE username = :u';
        $params = [':u' => $username];
        if ($ignorarId) { $sql .= ' AND id != :id'; $params[':id'] = $ignorarId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function escolaJaTemDiretor(int $idEscola, ?int $ignorarId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM usuarios WHERE id_escola = :esc AND perfil = 'diretor' AND ativo = 1";
        $params = [':esc' => $idEscola];
        if ($ignorarId) { $sql .= ' AND id != :id'; $params[':id'] = $ignorarId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nome, username, email, senha_hash, perfil, categoria, id_escola)
             VALUES (:nome, :username, :email, :hash, :perfil, :categoria, :escola)'
        );
        $stmt->execute([
            ':nome'      => $dados['nome'],
            ':username'  => $dados['username'],
            ':email'     => $dados['email'],
            ':hash'      => $dados['senha_hash'],
            ':perfil'    => $dados['perfil'],
            ':categoria' => $dados['categoria'] ?? null,
            ':escola'    => $dados['id_escola']  ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET nome = :nome, username = :username, email = :email,
                 perfil = :perfil, categoria = :categoria, id_escola = :escola
             WHERE id = :id'
        );
        $stmt->execute([
            ':nome'      => $dados['nome'],
            ':username'  => $dados['username'],
            ':email'     => $dados['email'],
            ':perfil'    => $dados['perfil'],
            ':categoria' => $dados['categoria'] ?? null,
            ':escola'    => $dados['id_escola']  ?? null,
            ':id'        => $id,
        ]);
    }

    public function alternarAtivo(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET ativo = NOT ativo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $stmt2 = $this->db->prepare('SELECT ativo FROM usuarios WHERE id = :id');
        $stmt2->execute([':id' => $id]);
        return (bool) $stmt2->fetchColumn();
    }

    public function listarTodosSuperadmins(): array
    {
        return $this->db->query(
            "SELECT id FROM usuarios WHERE perfil = 'superadmin' AND ativo = 1"
        )->fetchAll(\PDO::FETCH_COLUMN);
    }
}
