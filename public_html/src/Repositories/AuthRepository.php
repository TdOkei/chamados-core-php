<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class AuthRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Busca usuário por username ou email.
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nome, username, email, senha_hash, perfil, categoria,
                    id_escola, ativo, tentativas_login, bloqueado, ultimo_login, trocar_senha
             FROM usuarios
             WHERE (username = :id1 OR email = :id2)
             LIMIT 1'
        );
        $stmt->execute([':id1' => $identifier, ':id2' => $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Busca usuário por ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nome, username, email, perfil, categoria,
                    id_escola, ativo, tentativas_login, bloqueado
             FROM usuarios
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Incrementa tentativas de login.
     */
    public function incrementarTentativas(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET tentativas_login = tentativas_login + 1
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Bloqueia conta após exceder limite de tentativas.
     */
    public function bloquearConta(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET bloqueado = 1
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Reseta tentativas e desbloqueia conta (ao redefinir senha).
     */
    public function desbloquearConta(int $id, string $novaSenhaHash): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET bloqueado = 0,
                 tentativas_login = 0,
                 senha_hash = :hash
             WHERE id = :id'
        );
        $stmt->execute([':hash' => $novaSenhaHash, ':id' => $id]);
    }

    /**
     * Registra data/hora do último login e reseta tentativas.
     */
    public function registrarLogin(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET ultimo_login = NOW(),
                 tentativas_login = 0
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Atualiza senha do usuário.
     */
    public function atualizarSenha(int $id, string $novaSenhaHash): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios
             SET senha_hash = :hash,
                 tentativas_login = 0,
                 bloqueado = 0,
                 trocar_senha = 0
             WHERE id = :id'
        );
        $stmt->execute([':hash' => $novaSenhaHash, ':id' => $id]);
    }

    /**
     * Salva sessão no banco (rastreamento de segurança).
     */
    public function salvarSessao(int $idUsuario, string $token, string $ip, string $userAgent): void
    {
        // Remove sessões antigas do usuário
        $del = $this->db->prepare('DELETE FROM sessoes WHERE id_usuario = :id');
        $del->execute([':id' => $idUsuario]);

        $expira = date('Y-m-d H:i:s', time() + 18000); // 5h
        $stmt   = $this->db->prepare(
            'INSERT INTO sessoes (id_usuario, session_token, ip, user_agent, ultimo_acesso, expira_em)
             VALUES (:uid, :token, :ip, :ua, NOW(), :exp)'
        );
        $stmt->execute([
            ':uid'   => $idUsuario,
            ':token' => $token,
            ':ip'    => $ip,
            ':ua'    => $userAgent,
            ':exp'   => $expira,
        ]);
    }

    /**
     * Remove sessão do banco no logout.
     */
    public function removerSessao(int $idUsuario): void
    {
        $stmt = $this->db->prepare('DELETE FROM sessoes WHERE id_usuario = :id');
        $stmt->execute([':id' => $idUsuario]);
    }

    /**
     * Lista usuários bloqueados (para superadmin).
     */
    public function listarBloqueados(): array
    {
        $stmt = $this->db->query(
            'SELECT u.id, u.nome, u.username, u.email, u.perfil, u.tentativas_login,
                    e.nome AS escola_nome
             FROM usuarios u
             LEFT JOIN escolas e ON e.id = u.id_escola
             WHERE u.bloqueado = 1
             ORDER BY u.nome'
        );
        return $stmt->fetchAll();
    }
}
