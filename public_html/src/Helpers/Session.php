<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\App;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        if (!self::has('usuario_id') || !self::has('ultimo_acesso')) {
            return false;
        }

        // Verifica timeout de sessão (5 horas)
        $ultimoAcesso = self::get('ultimo_acesso');
        if ((time() - $ultimoAcesso) > App::SESSION_TIMEOUT) {
            self::destroy();
            return false;
        }

        // Atualiza timestamp de acesso
        self::set('ultimo_acesso', time());
        return true;
    }

    public static function login(array $usuario): void
    {
        session_regenerate_id(true);
        self::set('usuario_id',   $usuario['id']);
        self::set('usuario_nome', $usuario['nome']);
        self::set('session_inicio', time());
        self::set('usuario_perfil', $usuario['perfil']);
        self::set('usuario_email', $usuario['email']);
        self::set('usuario_categoria', $usuario['categoria'] ?? null);
        self::set('id_escola',    $usuario['id_escola'] ?? null);
        self::set('ultimo_acesso', time());
    }

    public static function getUsuarioId(): ?int
    {
        $id = self::get('usuario_id');
        return $id !== null ? (int) $id : null;
    }

    public static function getPerfil(): ?string
    {
        return self::get('usuario_perfil');
    }

    public static function getCategoria(): ?string
    {
        return self::get('usuario_categoria');
    }

    public static function getIdEscola(): ?int
    {
        $id = self::get('id_escola');
        return $id !== null ? (int) $id : null;
    }

    public static function isSuperadmin(): bool
    {
        return self::getPerfil() === App::PERFIL_SUPERADMIN;
    }

    public static function isAdmin(): bool
    {
        return self::getPerfil() === App::PERFIL_ADMINISTRADOR;
    }

    public static function isDiretor(): bool
    {
        return in_array(self::getPerfil(), App::PERFIS_OPERACIONAIS_ESCOLA, true);
    }

    // Mensagem flash (exibida uma vez)
    public static function flash(string $tipo, string $mensagem): void
    {
        self::set("flash_{$tipo}", $mensagem);
    }

    public static function getFlash(string $tipo): ?string
    {
        $msg = self::get("flash_{$tipo}");
        self::remove("flash_{$tipo}");
        return $msg;
    }
}
