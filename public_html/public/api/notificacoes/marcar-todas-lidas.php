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

$service = new NotificacaoService();
$service->marcarTodasLidas(Session::getUsuarioId());

Response::success('Todas as notificações marcadas como lidas.');
