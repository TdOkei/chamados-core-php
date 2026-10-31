<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\SuperadminService;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

AuthMiddleware::requireAdmin(isApi: true);

$method  = $_SERVER['REQUEST_METHOD'];
$id      = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$acao    = $_GET['acao'] ?? '';
$service = new SuperadminService();

switch ($method) {
    case 'GET':
        $dados = $service->listarUnidades(apenasAtivas: true);
        Response::success('OK', ['unidades' => $dados]);

    case 'POST':
        AuthMiddleware::requireSuperadmin(isApi: true);
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $acao === 'toggle' && $id
            ? $service->alternarAtivoUnidade((int) $id)
            : $service->criarUnidade($body);
        $result['success']
            ? Response::success($result['message'], $result['data'])
            : Response::error($result['message']);

    case 'PUT':
        AuthMiddleware::requireSuperadmin(isApi: true);
        if (!$id) Response::error('ID obrigatório.', 400);
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $service->atualizarUnidade((int) $id, $body);
        $result['success']
            ? Response::success($result['message'])
            : Response::error($result['message']);

    default:
        Response::error('Método não permitido.', 405);
}
