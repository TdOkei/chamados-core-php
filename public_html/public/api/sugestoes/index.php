<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\SuperadminService;
use App\Services\NotificacaoService;
use App\Services\AuthService;
use App\Repositories\SugestaoRepository;
use App\Repositories\UsuarioRepository;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Middleware\AuthMiddleware;
use App\Config\App;

AuthMiddleware::check(isApi: true);

$method  = $_SERVER['REQUEST_METHOD'];
$id      = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$perfil  = Session::getPerfil();

// GET: listar sugestões (superadmin) ou apenas contar (diretor)
if ($method === 'GET') {
    AuthMiddleware::requireSuperadmin(isApi: true);
    $status  = $_GET['status'] ?? null;
    $service = new SuperadminService();
    $dados   = $service->listarSugestoes($status ?: null);
    Response::success('OK', ['sugestoes' => $dados]);
}

// POST: criar sugestão (diretor) ou analisar (superadmin)
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = $body['acao'] ?? '';

    if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)) {
        // Diretor cria sugestão
        $descricao = trim($body['descricao'] ?? '');
        $link      = trim($body['link']      ?? '');
        if (empty($descricao)) Response::error('A descricao e obrigatoria.');
        if (strlen($descricao) > 400) Response::error('Descricao muito longa (max. 400 caracteres).');
        if ($link && strlen($link) > 500) Response::error('Link muito longo (max. 500 caracteres).');

        $idEscola  = Session::getIdEscola();
        $idDiretor = Session::getUsuarioId();

        $repo   = new SugestaoRepository();
        $idSug  = $repo->criar($idEscola, $idDiretor, $descricao, $link ?: null);

        // Notifica superadmin(s)
        $uRepo   = new UsuarioRepository();
        $admins  = $uRepo->listarTodosSuperadmins();
        $notif   = new NotificacaoService();
        $eRepo   = new \App\Repositories\EscolaRepository();
        $escolaObj = $eRepo->buscarPorId($idEscola);
        $escolaNome = $escolaObj['nome'] ?? Session::get('usuario_nome');
        foreach ($admins as $adminId) {
            $notif->novaSugestao((int) $adminId, $idSug, $escolaNome);
        }

        Response::success('Sugestão enviada com sucesso.', ['id' => $idSug]);
    }

    if ($perfil === App::PERFIL_SUPERADMIN) {
        // Superadmin analisa sugestão
        if (!$id) Response::error('ID obrigatório.');
        if (!in_array($acao, ['analisada', 'rejeitada'], true)) Response::error('Ação inválida.');

        $service = new SuperadminService();
        $result  = $service->analisarSugestao((int) $id, $acao);
        $result['success']
            ? Response::success($result['message'])
            : Response::error($result['message']);
    }

    Response::forbidden();
}

Response::error('Método não permitido.', 405);
