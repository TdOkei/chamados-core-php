<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\AuthService;
use App\Helpers\Response;
use App\Helpers\Session;

$service = new AuthService();
$service->logout();

// Redireciona para login com mensagem
Session::flash('info', 'Você saiu do sistema com segurança.');
Response::redirect('/index.php');
