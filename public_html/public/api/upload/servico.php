<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../src/bootstrap.php";

use App\Middleware\AuthMiddleware;
use App\Helpers\{Response, Session};
use App\Config\App;

AuthMiddleware::requireDiretor(isApi: true);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    Response::error("Metodo nao permitido.", 405);
}

$idSolicitacao = (int) ($_POST["id_solicitacao"] ?? 0);
if (!$idSolicitacao) Response::error("ID da solicitacao obrigatorio.");

// Verifica se arquivo foi enviado
if (empty($_FILES["foto"]) || $_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
    Response::error("Nenhuma foto enviada ou erro no upload.");
}

$arquivo = $_FILES["foto"];

// Valida tipo
$tiposPermitidos = ["image/jpeg", "image/jpg", "image/png"];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$tipoReal = $finfo->file($arquivo["tmp_name"]);
if (!in_array($tipoReal, $tiposPermitidos, true)) {
    Response::error("Tipo de arquivo invalido. Envie JPG ou PNG.");
}

// Valida tamanho (5MB)
if ($arquivo["size"] > 5 * 1024 * 1024) {
    Response::error("Arquivo muito grande. Maximo 5MB.");
}

// Gera nome unico
$ext      = $tipoReal === "image/png" ? "png" : "jpg";
$nomeArq  = "sol_{$idSolicitacao}_" . time() . "_" . bin2hex(random_bytes(4)) . ".{$ext}";
$destino  = rtrim($_ENV['UPLOADS_PATH'] ?? '/var/www/uploads', '/') . "/servicos/{$nomeArq}";

if (!move_uploaded_file($arquivo["tmp_name"], $destino)) {
    Response::error("Erro ao salvar a foto no servidor.");
}

// Atualiza banco
$db   = \App\Config\Database::getInstance();
$stmt = $db->prepare("UPDATE solicitacoes SET foto_servico = :foto WHERE id = :id AND id_escola = :esc");
$stmt->execute([
    ":foto" => $nomeArq,
    ":id"   => $idSolicitacao,
    ":esc"  => Session::getIdEscola(),
]);

if ($stmt->rowCount() === 0) {
    // Apaga arquivo se nao atualizou (solicitacao nao pertence ao diretor)
    @unlink($destino);
    Response::error("Solicitacao nao encontrada ou sem permissao.");
}

Response::success("Foto enviada com sucesso.", ["arquivo" => $nomeArq]);
