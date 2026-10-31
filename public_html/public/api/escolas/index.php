<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\SuperadminService;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

AuthMiddleware::requireSecretario(isApi: true);

$method = $_SERVER['REQUEST_METHOD'];
$id     = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$acao   = $_GET['acao'] ?? '';
$service= new SuperadminService();

switch ($method) {
    case 'GET':
        $dados = $service->listarEscolas();
        Response::success('OK', ['escolas' => $dados]);

    case 'POST':
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $acao === 'toggle' && $id
            ? $service->alternarAtivoEscola((int) $id)
            : $service->criarEscola($body);

        $result['success']
            ? Response::success($result['message'], $result['data'])
            : Response::error($result['message']);

    case 'PUT':
        if (!$id) Response::error('ID obrigatório.', 400);
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $service->atualizarEscola((int) $id, $body);
        $result['success']
            ? Response::success($result['message'])
            : Response::error($result['message']);

    default:
        Response::error('Método não permitido.', 405);
}
