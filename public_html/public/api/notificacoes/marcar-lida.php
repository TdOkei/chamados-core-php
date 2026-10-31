<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\NotificacaoService;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Middleware\AuthMiddleware;

AuthMiddleware::check(isApi: true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido.', 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$id   = filter_var($body['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    Response::error('ID inválido.');
}

$service = new NotificacaoService();
$service->marcarLida((int) $id, Session::getUsuarioId());

Response::success('Notificação marcada como lida.');
