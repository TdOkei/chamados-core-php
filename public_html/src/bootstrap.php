<?php

declare(strict_types=1);

function loadEnv(string $path): void
{
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, '"\'');
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// .env está em public_html/.env (um nível acima de src/)
loadEnv(__DIR__ . '/../.env');

$debug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    set_exception_handler(function (\Throwable $e) {
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
        } else {
            http_response_code(500);
            include __DIR__ . '/../public/404.php';
        }
        exit;
    });
}

spl_autoload_register(function (string $class): void {
    $prefix   = 'App\\';
    $base_dir = __DIR__ . '/';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

if (session_status() === PHP_SESSION_NONE) {
    session_name($_ENV['SESSION_NAME'] ?? 'PHPSESSID');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_secure', ($_ENV['APP_ENV'] ?? 'production') === 'production' ? '1' : '0');
    ini_set('session.gc_maxlifetime', '14400');
    ini_set('session.cookie_lifetime', '14400');
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

// Gerar token CSRF se nao existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validar token CSRF em requisicoes POST de API (exceto login e renovacao)
$uriAtual = $_SERVER['REQUEST_URI'] ?? '';
$rotasExcluidas = ['/api/auth/login.php', '/api/auth/renovar-sessao.php'];
$isRotaExcluida = false;
foreach ($rotasExcluidas as $rota) {
    if (str_contains($uriAtual, $rota)) { $isRotaExcluida = true; break; }
}
if (
    str_contains($uriAtual, '/api/') &&
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
    !$isRotaExcluida
) {
    $tokenEnviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $tokenSessao  = $_SESSION['csrf_token'] ?? '';
    if (!$tokenSessao || !hash_equals($tokenSessao, $tokenEnviado)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Token de seguranca invalido.']);
        exit;
    }
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
