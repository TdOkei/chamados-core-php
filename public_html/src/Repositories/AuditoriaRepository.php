<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class AuditoriaRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listar(array $filtros = [], int $limite = 100, int $offset = 0): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['acao'])) {
            $where[]          = 'a.acao = :acao';
            $params[':acao']  = $filtros['acao'];
        }
        if (!empty($filtros['entidade'])) {
            $where[]              = 'a.entidade = :entidade';
            $params[':entidade']  = $filtros['entidade'];
        }
        if (!empty($filtros['id_usuario'])) {
            $where[]               = 'a.id_usuario = :uid';
            $params[':uid']        = $filtros['id_usuario'];
        }
        if (!empty($filtros['de'])) {
            $where[]       = 'a.created_at >= :de';
            $params[':de'] = $filtros['de'] . ' 00:00:00';
        }
        if (!empty($filtros['ate'])) {
            $where[]        = 'a.created_at <= :ate';
            $params[':ate'] = $filtros['ate'] . ' 23:59:59';
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT a.*, u.nome AS usuario_nome
            FROM log_auditoria a
            LEFT JOIN usuarios u ON u.id = a.id_usuario
            {$whereStr}
            ORDER BY a.created_at DESC
            LIMIT {$limite} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function contar(array $filtros = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['acao'])) {
            $where[]         = 'acao = :acao';
            $params[':acao'] = $filtros['acao'];
        }
        if (!empty($filtros['entidade'])) {
            $where[]             = 'entidade = :entidade';
            $params[':entidade'] = $filtros['entidade'];
        }
        if (!empty($filtros['id_usuario'])) {
            $where[]        = 'id_usuario = :uid';
            $params[':uid'] = $filtros['id_usuario'];
        }
        if (!empty($filtros['de'])) {
            $where[]       = 'created_at >= :de';
            $params[':de'] = $filtros['de'] . ' 00:00:00';
        }
        if (!empty($filtros['ate'])) {
            $where[]        = 'created_at <= :ate';
            $params[':ate'] = $filtros['ate'] . ' 23:59:59';
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM log_auditoria {$whereStr}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listarUsuarios(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT a.id_usuario, u.nome
             FROM log_auditoria a
             LEFT JOIN usuarios u ON u.id = a.id_usuario
             ORDER BY u.nome"
        );
        return $stmt->fetchAll();
    }
}
