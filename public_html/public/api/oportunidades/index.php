<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\OportunidadeService;
use App\Helpers\{Response, Session};
use App\Middleware\AuthMiddleware;
use App\Config\App;

AuthMiddleware::check(isApi: true);

$method    = $_SERVER['REQUEST_METHOD'];
$id        = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$acao      = $_GET['acao'] ?? '';
$perfil    = Session::getPerfil();
$idUsuario = Session::getUsuarioId();
$service   = new OportunidadeService();

// ---- GET: listar ----
if ($method === 'GET' && !$id) {
    if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)) {
        $dados = $service->listarParaEscola(Session::getIdEscola());
    } else {
        $dados = $service->listar($perfil === App::PERFIL_SUPERADMIN ? null : $idUsuario);
    }
    Response::success('OK', ['oportunidades' => $dados]);
}

// ---- GET: detalhe ----
if ($method === 'GET' && $id) {
    $op = $service->buscarDetalhes((int) $id);
    if (!$op) Response::notFound('Oportunidade não encontrada.');
    Response::success('OK', ['oportunidade' => $op]);
}

// ---- POST: criar (admin) ----
if ($method === 'POST' && !$acao && !$id) {
    AuthMiddleware::requireAdmin(isApi: true);
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = $service->criar($body, $idUsuario);
    $result['success']
        ? Response::success($result['message'], $result['data'])
        : Response::error($result['message']);
}

// ---- POST: decidir (diretor) ----
if ($method === 'POST' && $acao === 'decidir' && $id) {
    AuthMiddleware::requireOportunidadesEscola(isApi: true);
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $decisao    = $body['decisao']   ?? '';
    $quantidade = isset($body['quantidade']) ? (float) $body['quantidade'] : null;
    $result  = $service->decidir(
        (int) $id,
        $decisao,
        $idUsuario,
        Session::getIdEscola(),
        Session::get('usuario_nome'),
        $quantidade
    );
    $result['success']
        ? Response::success($result['message'], $result['data'])
        : Response::error($result['message']);
}

// ---- PUT: editar (admin) ----
if ($method === 'PUT' && $id) {
    AuthMiddleware::requireAdmin(isApi: true);
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = $service->atualizar((int) $id, $body, $idUsuario);
    $result['success']
        ? Response::success($result['message'])
        : Response::error($result['message']);
}

// ---- POST: desabilitar (admin) ----
if ($method === 'POST' && $acao === 'desabilitar' && $id) {
    AuthMiddleware::requireAdmin(isApi: true);
    $result = $service->desabilitar((int) $id, $idUsuario);
    $result['success']
        ? Response::success($result['message'])
        : Response::error($result['message']);
}

Response::error('Requisição inválida.', 400);
