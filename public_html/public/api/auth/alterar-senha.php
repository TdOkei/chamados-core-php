<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\AuthService;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Middleware\AuthMiddleware;

AuthMiddleware::check(isApi: true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido.', 405);
}

$body = json_decode(file_get_contents('php://input'), true);

$senhaAtual  = $body['senha_atual']   ?? '';
$novaSenha   = $body['nova_senha']    ?? '';
$confirmacao = $body['confirmacao']   ?? '';
$idUsuario   = Session::getUsuarioId();

$service = new AuthService();
$result  = $service->redefinirSenhaPropria($idUsuario, $senhaAtual, $novaSenha, $confirmacao);

if ($result['success']) {
    Response::success($result['message']);
} else {
    Response::error($result['message']);
}
