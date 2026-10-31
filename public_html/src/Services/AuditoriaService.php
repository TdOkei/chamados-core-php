<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Helpers\Session;

class AuditoriaService
{
    private static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'desconhecido';
    }

    public static function registrar(
        string $acao,
        string $entidade,
        string $descricao,
        ?int   $idEntidade = null,
        ?int   $idUsuario  = null,
        ?string $perfil    = null
    ): void {
        try {
            $db   = Database::getInstance();
            $id   = $idUsuario ?? Session::getUsuarioId();
            $perf = $perfil    ?? Session::getPerfil() ?? 'sistema';

            if ($id === null) return;

            $stmt = $db->prepare(
                "INSERT INTO log_auditoria (id_usuario, perfil, acao, entidade, id_entidade, descricao, ip)
                 VALUES (:uid, :perfil, :acao, :entidade, :id_ent, :desc, :ip)"
            );
            $stmt->execute([
                ':uid'      => $id,
                ':perfil'   => $perf,
                ':acao'     => $acao,
                ':entidade' => $entidade,
                ':id_ent'   => $idEntidade,
                ':desc'     => $descricao,
                ':ip'       => self::ip(),
            ]);
        } catch (\Throwable $e) {
            // Auditoria nunca deve quebrar a aplicacao
            error_log("AuditoriaService erro: " . $e->getMessage());
        }
    }

    // Atalhos semanticos
    public static function usuario(string $acao, int $id, string $desc): void
    {
        self::registrar($acao, "usuario", $desc, $id);
    }

    public static function solicitacao(string $acao, int $id, string $desc): void
    {
        self::registrar($acao, "solicitacao", $desc, $id);
    }

    public static function produto(string $acao, int $id, string $desc): void
    {
        self::registrar($acao, "produto", $desc, $id);
    }

    public static function escola(string $acao, int $id, string $desc): void
    {
        self::registrar($acao, "escola", $desc, $id);
    }

    public static function oportunidade(string $acao, int $id, string $desc): void
    {
        self::registrar($acao, "oportunidade", $desc, $id);
    }

    public static function exportacao(string $desc): void
    {
        self::registrar("exportar", "relatorio", $desc, null);
    }
}
