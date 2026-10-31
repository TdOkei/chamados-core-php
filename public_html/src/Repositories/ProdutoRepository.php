<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class ProdutoRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(bool $apenasAtivos = false): array
    {
        $where = $apenasAtivos ? 'WHERE ativo = 1' : '';
        return $this->db->query(
            "SELECT id, nome, categoria, descricao, ativo, created_at FROM produtos {$where} ORDER BY nome"
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function nomeExiste(string $nome, ?int $ignorarId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM produtos WHERE nome = :nome';
        $params = [':nome' => $nome];
        if ($ignorarId) { $sql .= ' AND id != :id'; $params[':id'] = $ignorarId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO produtos (nome, categoria, descricao) VALUES (:nome, :cat, :desc)'
        );
        $stmt->execute([':nome' => $dados['nome'], ':cat' => $dados['categoria'] ?? 'outros', ':desc' => $dados['descricao'] ?? null]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->db->prepare(
            'UPDATE produtos SET nome = :nome, categoria = :cat, descricao = :desc WHERE id = :id'
        );
        $stmt->execute([':nome' => $dados['nome'], ':cat' => $dados['categoria'] ?? 'outros', ':desc' => $dados['descricao'] ?? null, ':id' => $id]);
    }

    public function alternarAtivo(int $id): bool
    {
        $this->db->prepare('UPDATE produtos SET ativo = NOT ativo WHERE id = :id')->execute([':id' => $id]);
        $stmt = $this->db->prepare('SELECT ativo FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function estaEmUso(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM solicitacao_itens WHERE id_produto = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

class UnidadeRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(bool $apenasAtivas = false): array
    {
        $where = $apenasAtivas ? 'WHERE ativo = 1' : '';
        return $this->db->query(
            "SELECT id, nome, sigla, ativo, created_at FROM unidades_medida {$where} ORDER BY nome"
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM unidades_medida WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function siglaExiste(string $sigla, ?int $ignorarId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM unidades_medida WHERE sigla = :s';
        $params = [':s' => $sigla];
        if ($ignorarId) { $sql .= ' AND id != :id'; $params[':id'] = $ignorarId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO unidades_medida (nome, sigla) VALUES (:nome, :sigla)'
        );
        $stmt->execute([':nome' => $dados['nome'], ':sigla' => strtoupper($dados['sigla'])]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->db->prepare(
            'UPDATE unidades_medida SET nome = :nome, sigla = :sigla WHERE id = :id'
        );
        $stmt->execute([':nome' => $dados['nome'], ':sigla' => strtoupper($dados['sigla']), ':id' => $id]);
    }

    public function alternarAtivo(int $id): bool
    {
        $this->db->prepare('UPDATE unidades_medida SET ativo = NOT ativo WHERE id = :id')->execute([':id' => $id]);
        $stmt = $this->db->prepare('SELECT ativo FROM unidades_medida WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function estaEmUso(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM solicitacao_itens WHERE id_unidade = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
