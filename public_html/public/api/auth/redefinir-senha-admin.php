<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\AuthService;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

// Apenas superadmin
AuthMiddleware::requireSuperadmin(isApi: true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido.', 405);
}

$body        = json_decode(file_get_contents('php://input'), true);
$idAlvo      = filter_var($body['id_usuario'] ?? 0, FILTER_VALIDATE_INT);
$novaSenha   = $body['nova_senha']  ?? '';
$confirmacao = $body['confirmacao'] ?? '';

if (!$idAlvo || $idAlvo <= 0) {
    Response::error('Usuário inválido.');
}

$service = new AuthService();
$result  = $service->redefinirSenhaPorAdmin((int) $idAlvo, $novaSenha, $confirmacao);

if ($result['success']) {
    Response::success($result['message']);
} else {
    Response::error($result['message']);
}
