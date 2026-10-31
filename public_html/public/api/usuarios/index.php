<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\SuperadminService;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

AuthMiddleware::requireSuperadmin(isApi: true);

$method  = $_SERVER['REQUEST_METHOD'];
$id      = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$acao    = $_GET['acao'] ?? '';
$service = new SuperadminService();

switch ($method) {
    case 'GET':
        $perfil = $_GET['perfil'] ?? null;
        $dados  = $service->listarUsuarios($perfil ?: null);
        Response::success('OK', ['usuarios' => $dados]);

    case 'POST':
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $acao === 'toggle' && $id
            ? $service->alternarAtivoUsuario((int) $id)
            : $service->criarUsuario($body);
        $result['success']
            ? Response::success($result['message'], $result['data'])
            : Response::error($result['message']);

    case 'PUT':
        if (!$id) Response::error('ID obrigatório.', 400);
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $service->atualizarUsuario((int) $id, $body);
        $result['success']
            ? Response::success($result['message'])
            : Response::error($result['message']);

    default:
        Response::error('Método não permitido.', 405);
}
