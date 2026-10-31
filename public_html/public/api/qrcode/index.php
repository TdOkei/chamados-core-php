<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\QrCodeService;
use App\Helpers\{Response, Session};
use App\Middleware\AuthMiddleware;

AuthMiddleware::requireDiretor(isApi: true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido.', 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$acao   = $body['acao']   ?? '';
$token  = $body['token']  ?? '';
$service= new QrCodeService();
$idDir  = Session::getUsuarioId();
$idEsc  = Session::getIdEscola();

// Validar token (sem alterar status)
if ($acao === 'validar') {
    $result = $service->validarToken($token, $idDir, $idEsc);
    $result['success']
        ? Response::success($result['message'], $result['data'])
        : Response::error($result['message']);
}

// Processar resultado definitivo
if ($acao === 'processar') {
    $cenario   = $body['cenario']    ?? '';
    $observacao= $body['observacao'] ?? '';
    $result    = $service->processarResultado($token, $idDir, $idEsc, $cenario, $observacao, $body);
    $result['success']
        ? Response::success($result['message'], $result['data'])
        : Response::error($result['message']);
}

Response::error('Ação inválida.', 400);
