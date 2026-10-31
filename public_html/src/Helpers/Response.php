<?php

declare(strict_types=1);

namespace App\Helpers;

class Response
{
    public static function json(bool $success, string $message, array $data = [], int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message = 'Operação realizada com sucesso.', array $data = []): void
    {
        self::json(true, $message, $data, 200);
    }

    public static function error(string $message = 'Erro ao processar a solicitação.', int $code = 400, array $data = []): void
    {
        self::json(false, $message, $data, $code);
    }

    public static function unauthorized(string $message = 'Acesso não autorizado.'): void
    {
        self::json(false, $message, [], 401);
    }

    public static function forbidden(string $message = 'Você não tem permissão para esta ação.'): void
    {
        self::json(false, $message, [], 403);
    }

    public static function notFound(string $message = 'Recurso não encontrado.'): void
    {
        self::json(false, $message, [], 404);
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
