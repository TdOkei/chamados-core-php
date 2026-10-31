<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Repositories\HistoricoRepository;
use App\Services\ExportService;
use App\Services\AuditoriaService;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

AuthMiddleware::requireSecretario(isApi: true);

$method = $_SERVER['REQUEST_METHOD'];
$acao   = filter_var($_GET['acao'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

// Valores enumerados — validação estrita
$statusValidos    = ['', 'concluido', 'cancelado', 'rejeitado'];
$categoriasValidas= ['', 'produto', 'servico'];

$filtros = [
    'status'      => in_array($_GET['status']    ?? '', $statusValidos, true)    ? ($_GET['status']    ?? '') : '',
    'categoria'   => in_array($_GET['categoria'] ?? '', $categoriasValidas, true) ? ($_GET['categoria'] ?? '') : '',
    'id_escola'   => filter_var($_GET['id_escola']   ?? '', FILTER_VALIDATE_INT) ?: '',
    'data_inicio' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_inicio'] ?? '') ? $_GET['data_inicio'] : '',
    'data_fim'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim']    ?? '') ? $_GET['data_fim']    : '',
    'busca'       => mb_substr(trim($_GET['busca'] ?? ''), 0, 100),
];
// Remove filtros vazios
$filtros = array_filter($filtros, fn($v) => $v !== '');

if ($method === 'GET' && $acao === 'exportar') {
    // Gera e envia o Excel
    $service  = new ExportService();
    $filePath = $service->gerarExcelHistorico($filtros);

    if (!$filePath) {
        Response::error('Erro ao gerar o arquivo Excel. Verifique se há dados no período selecionado.');
    }

    $desc = 'Exportacao Excel do historico';
    if (!empty($filtros['escola'])) $desc .= " (escola: {$filtros['escola']})";
    if (!empty($filtros['de']))     $desc .= " de {$filtros['de']}";
    if (!empty($filtros['ate']))    $desc .= " ate {$filtros['ate']}";
    AuditoriaService::exportacao($desc);

    $filename = 'historico-solicitacoes-' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($filePath);
    @unlink($filePath);
    exit;
}

if ($method === 'GET') {
    $repo    = new HistoricoRepository();
    $dados   = $repo->listar($filtros);
    $totais  = $repo->totais($filtros);
    $escolas = $repo->listarEscolas();
    Response::success('OK', [
        'solicitacoes' => $dados,
        'totais'       => $totais,
        'escolas'      => $escolas,
    ]);
}

Response::error('Método não permitido.', 405);
