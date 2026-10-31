<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Helpers\Session;

AuthMiddleware::check(isApi: true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Renova o timestamp da sessao
    Session::set('session_inicio', time());
    Response::success('Sessao renovada com sucesso.');
}

Response::error('Metodo nao permitido.', 405);
