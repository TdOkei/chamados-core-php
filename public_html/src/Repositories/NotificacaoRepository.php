<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;

class NotificacaoRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Cria uma nova notificação para um usuário.
     */
    public function criar(int $idUsuario, string $tipo, string $titulo, string $mensagem, ?int $idRef = null, ?string $refTipo = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notificacoes (id_usuario, tipo, titulo, mensagem, id_ref, ref_tipo)
             VALUES (:uid, :tipo, :titulo, :msg, :id_ref, :ref_tipo)'
        );
        $stmt->execute([
            ':uid'      => $idUsuario,
            ':tipo'     => $tipo,
            ':titulo'   => $titulo,
            ':msg'      => $mensagem,
            ':id_ref'   => $idRef,
            ':ref_tipo' => $refTipo,
        ]);
    }

    /**
     * Lista notificações do usuário (últimas 50, mais recentes primeiro).
     */
    public function listar(int $idUsuario, int $limite = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tipo, titulo, mensagem, lida, id_ref, ref_tipo, created_at
             FROM notificacoes
             WHERE id_usuario = :uid
             ORDER BY created_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':uid', $idUsuario, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limite,    \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Conta notificações não lidas.
     */
    public function contarNaoLidas(int $idUsuario): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notificacoes WHERE id_usuario = :uid AND lida = 0'
        );
        $stmt->execute([':uid' => $idUsuario]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca uma notificação como lida.
     */
    public function marcarLida(int $id, int $idUsuario): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notificacoes SET lida = 1 WHERE id = :id AND id_usuario = :uid'
        );
        $stmt->execute([':id' => $id, ':uid' => $idUsuario]);
    }

    /**
     * Marca todas as notificações do usuário como lidas.
     */
    public function marcarTodasLidas(int $idUsuario): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notificacoes SET lida = 1 WHERE id_usuario = :uid AND lida = 0'
        );
        $stmt->execute([':uid' => $idUsuario]);
    }

    /**
     * Contagem para badges de navegação por contexto.
     */
    public function contarBadges(int $idUsuario, string $perfil): array
    {
        $badges = [];

        if ($perfil === 'administrador') {
            // Fila: solicitações pendentes da categoria do admin
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM notificacoes
                 WHERE id_usuario = :uid AND tipo = :tipo AND lida = 0'
            );
            $stmt->execute([':uid' => $idUsuario, ':tipo' => 'nova_solicitacao']);
            $badges['fila'] = (int) $stmt->fetchColumn();
        }

        if (in_array($perfil, ['diretor','escriturario'], true)) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM notificacoes
                 WHERE id_usuario = :uid AND tipo = :tipo AND lida = 0'
            );
            $stmt->execute([':uid' => $idUsuario, ':tipo' => 'nova_oportunidade']);
            $badges['oportunidades'] = (int) $stmt->fetchColumn();
        }

        if ($perfil === 'superadmin') {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM notificacoes
                 WHERE id_usuario = :uid AND tipo = :tipo AND lida = 0'
            );
            $stmt->execute([':uid' => $idUsuario, ':tipo' => 'nova_sugestao']);
            $badges['sugestoes'] = (int) $stmt->fetchColumn();
        }

        return $badges;
    }
}
