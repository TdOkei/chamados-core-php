<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\SolicitacaoService;
use App\Helpers\{Response, Session};
use App\Middleware\AuthMiddleware;
use App\Config\App;

AuthMiddleware::check(isApi: true);

$method    = $_SERVER['REQUEST_METHOD'];
$id        = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$acao      = $_GET['acao'] ?? '';
$perfil    = Session::getPerfil();
$idUsuario = Session::getUsuarioId();
$service   = new SolicitacaoService();

// ---- GET: detalhe ----
if ($method === 'GET' && $id) {
    $sol = $service->buscarPorId((int) $id);
    if (!$sol) Response::notFound('Solicitação não encontrada.');
    Response::success('OK', ['solicitacao' => $sol]);
}

// ---- POST: criar (diretor) ----
if ($method === 'POST' && !$acao) {
    AuthMiddleware::requireDiretor(isApi: true);
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $idEscola = Session::getIdEscola();
    $nome     = Session::get('usuario_nome');

    $result = $service->criar($body, $idUsuario, $idEscola, $nome);
    $result['success']
        ? Response::success($result['message'], $result['data'])
        : Response::error($result['message']);
}

// ---- POST: cancelar (diretor) ----
if ($method === 'POST' && $acao === 'cancelar' && $id) {
    AuthMiddleware::requireDiretor(isApi: true);
    $result = $service->cancelar((int) $id, $idUsuario, Session::getIdEscola());
    $result['success']
        ? Response::success($result['message'])
        : Response::error($result['message']);
}

// ---- POST: mudar status (admin) ----
if ($method === 'POST' && $acao === 'status' && $id) {
    AuthMiddleware::requireAdmin(isApi: true);
    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $novoStatus  = $body['status']           ?? '';
    $observacao  = $body['observacao']       ?? null;
    $motivo      = $body['motivo_rejeicao']  ?? null;

    // Superadmin pode mexer em ambas categorias; admin só na sua
    $categoria = $perfil === App::PERFIL_SUPERADMIN ? ($body['categoria_override'] ?? '') : Session::getCategoria();

    if ($novoStatus === App::STATUS_REJEITADO) {
        $result = $service->rejeitar((int) $id, $idUsuario, $categoria, $motivo ?? '');
    } else {
        $result = $service->atualizarStatus((int) $id, $novoStatus, $idUsuario, $categoria, $observacao);
    }
    $result['success']
        ? Response::success($result['message'], $result['data'])
        : Response::error($result['message']);
}

Response::error('Requisição inválida.', 400);
