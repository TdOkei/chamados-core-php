<?php
// public/api/notificacoes/listar.php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\NotificacaoService;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Middleware\AuthMiddleware;

AuthMiddleware::check(isApi: true);

$service = new NotificacaoService();
$data    = $service->listar(Session::getUsuarioId());

Response::success('OK', $data);
