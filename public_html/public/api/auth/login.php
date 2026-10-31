<?php

declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\AuthService;
use App\Helpers\Response;
use App\Helpers\Session;

// Já logado
if (Session::isLoggedIn()) {
    Response::success('Já autenticado.', ['redirect' => '/dashboard.php']);
}

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido.', 405);
}

// Lê JSON
$body = json_decode(file_get_contents('php://input'), true);

$username  = trim(trim($body["username"] ?? ""));
$senha     = $body['senha'] ?? '';
$ip        = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// Rate limit simples por IP via sessão
$ipKey    = 'rl_' . md5($ip);
$attempts = Session::get($ipKey, 0);
if ($attempts > 20) {
    Response::error('Muitas requisições. Aguarde alguns minutos.', 429);
}
Session::set($ipKey, $attempts + 1);

$service = new AuthService();
$result  = $service->login($username, $senha, $ip, $userAgent);

if ($result['success']) {
    Session::remove($ipKey);
    Response::success($result['message'], $result['data']);
} else {
    Response::error($result['message'], 401, $result['data']);
}
