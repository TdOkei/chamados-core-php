<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../src/bootstrap.php";

use App\Middleware\AuthMiddleware;
use App\Helpers\Response;
use App\Config\Database;

AuthMiddleware::requireSuperadmin(isApi: true);

$method = $_SERVER["REQUEST_METHOD"];
$db     = Database::getInstance();
$base   = rtrim($_ENV['UPLOADS_PATH'] ?? '/var/www/uploads', '/') . "/servicos/";

// GET: lista fotos de abertura e de conclusão, com filtro de idade em meses (0 = todas)
if ($method === "GET") {
    $meses  = (int) ($_GET["meses"] ?? 3);
    $filtro = $meses > 0 ? "WHERE t.created_at <= DATE_SUB(NOW(), INTERVAL :meses MONTH)" : "";

    $sql = "
        SELECT t.* FROM (
            SELECT s.id, s.foto_servico AS foto_servico, 'abertura' AS tipo,
                   s.created_at AS created_at, s.status,
                   e.nome AS escola_nome, u.nome AS diretor_nome, s.descricao_servico
            FROM solicitacoes s
            JOIN escolas  e ON e.id = s.id_escola
            JOIN usuarios u ON u.id = s.id_diretor
            WHERE s.foto_servico IS NOT NULL
            UNION ALL
            SELECT s.id, s.foto_conclusao, 'conclusao',
                   COALESCE(
                       (SELECT MAX(h.created_at) FROM historico_status h
                         WHERE h.id_solicitacao = s.id AND h.status_novo = 'concluido'),
                       s.updated_at),
                   s.status,
                   e.nome, u.nome, s.descricao_servico
            FROM solicitacoes s
            JOIN escolas  e ON e.id = s.id_escola
            JOIN usuarios u ON u.id = s.id_diretor
            WHERE s.foto_conclusao IS NOT NULL
        ) t
        {$filtro}
        ORDER BY t.created_at ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($meses > 0 ? [":meses" => $meses] : []);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $path = $base . $row["foto_servico"];
        $row["tamanho"] = file_exists($path) ? filesize($path) : 0;
        $row["existe"]  = file_exists($path);
    }
    unset($row);

    Response::success("OK", ["imagens" => $rows, "total" => count($rows)]);
}

// POST: excluir imagens selecionadas (por nome de arquivo)
if ($method === "POST") {
    $body     = json_decode(file_get_contents("php://input"), true) ?? [];
    $arquivos = array_values(array_unique(array_map('strval', (array) ($body["arquivos"] ?? []))));

    if (empty($arquivos)) Response::error("Nenhuma imagem selecionada.");

    $removidos = 0;
    foreach ($arquivos as $nome) {
        if (!preg_match('/^(sol|con)_[0-9]+_[0-9]+_[a-f0-9]+\.(jpg|png)$/', $nome)) continue;

        $coluna = str_starts_with($nome, 'con_') ? 'foto_conclusao' : 'foto_servico';
        $stmt   = $db->prepare("UPDATE solicitacoes SET {$coluna} = NULL, updated_at = updated_at WHERE {$coluna} = :n");
        $stmt->execute([":n" => $nome]);
        if ($stmt->rowCount() === 0) continue;

        $arquivo = $base . $nome;
        if (file_exists($arquivo)) @unlink($arquivo);
        $removidos++;
    }

    Response::success("{$removidos} imagem(ns) removida(s) com sucesso.");
}

Response::error("Metodo nao permitido.", 405);
