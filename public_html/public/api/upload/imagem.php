<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../src/bootstrap.php";

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Config\App;

AuthMiddleware::check();

$arquivo = basename($_GET["arquivo"] ?? "");
if (!$arquivo || !preg_match('/^sol_[0-9]+_[0-9]+_[a-f0-9]+\.(jpg|png)$/', $arquivo)) {
    http_response_code(400);
    exit("Arquivo invalido.");
}

$caminho = rtrim($_ENV['UPLOADS_PATH'] ?? '/var/www/uploads', '/') . "/servicos/{$arquivo}";
if (!file_exists($caminho)) {
    http_response_code(404);
    exit("Arquivo nao encontrado.");
}

$mime = str_ends_with($arquivo, ".png") ? "image/png" : "image/jpeg";
header("Content-Type: {$mime}");
header("Content-Length: " . filesize($caminho));
header("Cache-Control: private, max-age=3600");
readfile($caminho);
exit;
