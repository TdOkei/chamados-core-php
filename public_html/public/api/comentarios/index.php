<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../src/bootstrap.php";

use App\Middleware\AuthMiddleware;
use App\Helpers\{Response, Session};
use App\Repositories\{ComentarioRepository, SolicitacaoRepository};
use App\Services\NotificacaoService;
use App\Config\App;

AuthMiddleware::check(isApi: true);

$method    = $_SERVER["REQUEST_METHOD"];
$perfil    = Session::getPerfil();
$idUsuario = Session::getUsuarioId();

// Apenas admin e diretor
if (!in_array($perfil, [App::PERFIL_ADMINISTRADOR, App::PERFIL_DIRETOR, App::PERFIL_SUPERADMIN, App::PERFIL_ESCRITURARIO, App::PERFIL_ESTAGIARIO], true)) {
    Response::forbidden("Sem permissao para comentar.");
}

$repo    = new ComentarioRepository();
$solRepo = new SolicitacaoRepository();

// GET: listar comentarios
if ($method === "GET") {
    $idSol = filter_var($_GET["id_solicitacao"] ?? 0, FILTER_VALIDATE_INT);
    if (!$idSol) Response::error("ID da solicitacao obrigatorio.");

    $comentarios = $repo->listar((int) $idSol);
    Response::success("OK", ["comentarios" => $comentarios]);
}

// POST: criar comentario
if ($method === "POST") {
    $body      = json_decode(file_get_contents("php://input"), true) ?? [];
    $idSol     = (int) ($body["id_solicitacao"] ?? 0);
    $mensagem  = trim($body["mensagem"] ?? "");

    if (!$idSol)     Response::error("ID da solicitacao obrigatorio.");
    if (empty($mensagem)) Response::error("A mensagem nao pode ser vazia.");
    if (mb_strlen($mensagem) > 1000) Response::error("Mensagem muito longa (max. 1000 caracteres).");

    // Verifica se solicitacao existe
    $solicitacao = $solRepo->buscarPorId($idSol);
    if (!$solicitacao) Response::notFound("Solicitacao nao encontrada.");

    // Diretor so comenta nas proprias solicitacoes
    if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) && (int) $solicitacao["id_escola"] !== Session::getIdEscola()) {
        Response::forbidden("Voce nao tem permissao para comentar nesta solicitacao.");
    }

    $id = $repo->criar($idSol, $idUsuario, $perfil, $mensagem);
    $comentario = $repo->buscarPorId($id);

    // Notificacoes
    $notif = new NotificacaoService();

    if (in_array($perfil, [App::PERFIL_ADMINISTRADOR, App::PERFIL_SUPERADMIN], true)) {
        // Admin comentou → notifica diretor
        if (!empty($solicitacao["id_diretor"])) {
            $notif->novoComentario((int) $solicitacao["id_diretor"], $idSol, $comentario["usuario_nome"]);
        }
    } else {
        // Diretor comentou → notifica admin responsavel
        if (!empty($solicitacao["id_admin"])) {
            $notif->novoComentario((int) $solicitacao["id_admin"], $idSol, $comentario["usuario_nome"]);
        }
    }

    Response::success("Comentario enviado.", ["comentario" => $comentario]);
}

Response::error("Metodo nao permitido.", 405);
