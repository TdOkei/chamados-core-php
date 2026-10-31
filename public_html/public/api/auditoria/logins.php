<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../src/bootstrap.php";

use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Config\Database;

AuthMiddleware::requireSuperadmin(isApi: true);

$db = Database::getInstance();

$idUsuario = $_GET["id_usuario"] ?? "";
$de        = $_GET["de"] ?? "";
$ate       = $_GET["ate"] ?? "";

$where  = ["acao IN ('login_sucesso', 'login_falha', 'conta_bloqueada')"];
$params = [];

if ($idUsuario !== "") {
    $where[]        = "id_usuario = :uid";
    $params[":uid"] = $idUsuario;
}
if ($de !== "") {
    $where[]       = "created_at >= :de";
    $params[":de"] = $de . " 00:00:00";
}
if ($ate !== "") {
    $where[]        = "created_at <= :ate";
    $params[":ate"] = $ate . " 23:59:59";
}

$whereStr = implode(" AND ", $where);

$limite = 50;
$pagina = max(1, (int) ($_GET["pagina"] ?? 1));
$offset = ($pagina - 1) * $limite;

$stmt = $db->prepare("
    SELECT a.*, u.nome AS usuario_nome
    FROM log_auditoria a
    LEFT JOIN usuarios u ON u.id = a.id_usuario
    WHERE {$whereStr}
    ORDER BY a.created_at DESC
    LIMIT {$limite} OFFSET {$offset}
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM log_auditoria WHERE {$whereStr}");
$stmtTotal->execute($params);
$total = (int) $stmtTotal->fetchColumn();

// Lista de usuarios distintos que possuem registros de login (incluindo o "anonimo" id=0)
$stmtUsers = $db->query("
    SELECT DISTINCT a.id_usuario, COALESCE(u.nome, 'Desconhecido / Anonimo') AS nome
    FROM log_auditoria a
    LEFT JOIN usuarios u ON u.id = a.id_usuario
    WHERE a.acao IN ('login_sucesso', 'login_falha', 'conta_bloqueada')
    ORDER BY nome
");
$usuarios = $stmtUsers->fetchAll();

Response::success("OK", [
    "logs"     => $logs,
    "total"    => $total,
    "pagina"   => $pagina,
    "limite"   => $limite,
    "usuarios" => $usuarios,
]);
