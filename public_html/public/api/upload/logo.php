<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Services\ConfiguracaoService;

AuthMiddleware::check();

$caminho = (new ConfiguracaoService())->caminhoLogo();
if (!$caminho) {
    http_response_code(404);
    exit('Logo não configurado.');
}

header('Content-Type: ' . (str_ends_with($caminho, '.png') ? 'image/png' : 'image/jpeg'));
header('Content-Length: ' . filesize($caminho));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($caminho);
exit;
