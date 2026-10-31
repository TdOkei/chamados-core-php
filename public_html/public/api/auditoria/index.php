<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../src/bootstrap.php";

use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Repositories\AuditoriaRepository;

AuthMiddleware::requireSuperadmin(isApi: true);

$filtros = [
    "acao"       => $_GET["acao"]       ?? "",
    "entidade"   => $_GET["entidade"]   ?? "",
    "id_usuario" => $_GET["id_usuario"] ?? "",
    "de"         => $_GET["de"]         ?? "",
    "ate"        => $_GET["ate"]        ?? "",
];
$filtros = array_filter($filtros, fn($v) => $v !== "");

$limite = 50;
$pagina = max(1, (int)($_GET["pagina"] ?? 1));
$offset = ($pagina - 1) * $limite;

$repo   = new AuditoriaRepository();
$logs   = $repo->listar($filtros, $limite, $offset);
$total  = $repo->contar($filtros);
$users  = $repo->listarUsuarios();

Response::success("OK", [
    "logs"     => $logs,
    "total"    => $total,
    "pagina"   => $pagina,
    "limite"   => $limite,
    "usuarios" => $users,
]);
