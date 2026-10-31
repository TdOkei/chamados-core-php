<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../src/bootstrap.php";

use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Config\Database;

AuthMiddleware::requireSuperadmin(isApi: true);

$method = $_SERVER["REQUEST_METHOD"];
$db     = Database::getInstance();

// GET: listar imagens com filtro de meses
if ($method === "GET") {
    $meses = (int) ($_GET["meses"] ?? 3);
    if ($meses <= 0) {
        // Mostrar todas as imagens
        $stmt = $db->prepare("
            SELECT s.id, s.foto_servico, s.created_at, s.status,
                   e.nome AS escola_nome, u.nome AS diretor_nome,
                   s.descricao_servico
            FROM solicitacoes s
            JOIN escolas  e ON e.id = s.id_escola
            JOIN usuarios u ON u.id = s.id_diretor
            WHERE s.foto_servico IS NOT NULL
            ORDER BY s.created_at ASC
        ");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT s.id, s.foto_servico, s.created_at, s.status,
                   e.nome AS escola_nome, u.nome AS diretor_nome,
                   s.descricao_servico
            FROM solicitacoes s
            JOIN escolas  e ON e.id = s.id_escola
            JOIN usuarios u ON u.id = s.id_diretor
            WHERE s.foto_servico IS NOT NULL
              AND s.created_at <= DATE_SUB(NOW(), INTERVAL :meses MONTH)
            ORDER BY s.created_at ASC
        ");
        $stmt->execute([":meses" => $meses]);
    }
    $rows = $stmt->fetchAll();

    // Adicionar tamanho do arquivo
    $base = rtrim($_ENV['UPLOADS_PATH'] ?? '/var/www/uploads', '/') . "/servicos/";
    foreach ($rows as &$row) {
        $path = $base . $row["foto_servico"];
        $row["tamanho"] = file_exists($path) ? filesize($path) : 0;
        $row["existe"]  = file_exists($path);
    }

    Response::success("OK", ["imagens" => $rows, "total" => count($rows)]);
}

// DELETE: excluir imagens selecionadas
if ($method === "POST") {
    $body = json_decode(file_get_contents("php://input"), true) ?? [];
    $ids  = array_map("intval", $body["ids"] ?? []);

    if (empty($ids)) Response::error("Nenhuma imagem selecionada.");

    $base    = rtrim($_ENV['UPLOADS_PATH'] ?? '/var/www/uploads', '/') . "/servicos/";
    $removidos = 0;

    foreach ($ids as $id) {
        $stmt = $db->prepare("SELECT foto_servico FROM solicitacoes WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $sol = $stmt->fetch();
        if (!$sol || !$sol["foto_servico"]) continue;

        $arquivo = $base . $sol["foto_servico"];
        if (file_exists($arquivo)) @unlink($arquivo);

        $db->prepare("UPDATE solicitacoes SET foto_servico = NULL WHERE id = :id")
           ->execute([":id" => $id]);
        $removidos++;
    }

    Response::success("{$removidos} imagem(ns) removida(s) com sucesso.");
}

Response::error("Metodo nao permitido.", 405);
