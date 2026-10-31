<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Repositories\RelatorioRepository;
use App\Services\CacheService;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

AuthMiddleware::requireSecretario(isApi: true);

// Suporta forçar atualização: ?refresh=1
$forcarAtualizacao = isset($_GET['refresh']) && $_GET['refresh'] === '1';

if ($forcarAtualizacao) {
    CacheService::deletePattern('relatorios:');
}

$repo = new RelatorioRepository();

// Filtros opcionais
$dataInicio = filter_var($_GET['data_inicio'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
$dataFim    = filter_var($_GET['data_fim']    ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
$idEscola   = filter_var($_GET['id_escola']   ?? '', FILTER_VALIDATE_INT) ?: null;

// Valida formato de data
if ($dataInicio && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) $dataInicio = '';
if ($dataFim    && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim))    $dataFim    = '';

$filtros = array_filter([
    'data_inicio' => $dataInicio ?: null,
    'data_fim'    => $dataFim    ?: null,
    'id_escola'   => $idEscola,
]);

// Cada bloco tem seu próprio TTL e chave de cache.
// Dados que mudam mais rapidamente têm TTL menor.
$dados = [
    'totais_gerais' => CacheService::remember(
        'relatorios:totais_gerais:' . md5(serialize($filtros)),
        CacheService::TTL_DASHBOARD,
        fn() => $repo->totaisGerais($filtros)
    ),
    'por_escola' => CacheService::remember(
        'relatorios:por_escola:' . md5(serialize($filtros)),
        CacheService::TTL_RELATORIOS,
        fn() => $repo->solicitacoesPorEscola($filtros)
    ),
    'por_mes' => CacheService::remember(
        'relatorios:por_mes:' . md5(serialize($filtros)),
        CacheService::TTL_RELATORIOS,
        fn() => $repo->solicitacoesPorMes($filtros)
    ),
    'por_status' => CacheService::remember(
        'relatorios:por_status:' . md5(serialize($filtros)),
        CacheService::TTL_DASHBOARD,
        fn() => $repo->distribuicaoPorStatus($filtros)
    ),
    'por_categoria' => CacheService::remember(
        'relatorios:por_categoria:' . md5(serialize($filtros)),
        CacheService::TTL_RELATORIOS,
        fn() => $repo->distribuicaoPorCategoria($filtros)
    ),
    'top_produtos' => CacheService::remember(
        'relatorios:top_produtos:' . md5(serialize($filtros)),
        CacheService::TTL_RELATORIOS,
        fn() => $repo->topProdutos(10, $filtros)
    ),
    'tempo_medio' => CacheService::remember(
        'relatorios:tempo_medio:' . md5(serialize($filtros)),
        CacheService::TTL_RELATORIOS,
        fn() => $repo->tempoMedioConclusao($filtros)
    ),
    'totais_qr' => CacheService::remember(
        'relatorios:totais_qr:' . md5(serialize($filtros)),
        CacheService::TTL_RELATORIOS,
        fn() => $repo->totaisQr($filtros)
    ),
    // Usuários bloqueados: sem cache (dado crítico de segurança, sempre atualizado)
    'usuarios_bloqueados' => $repo->usuariosBloqueados(),
    'itens_faltantes_recorrentes' => CacheService::remember(
        'relatorios:itens_faltantes:' . md5(serialize($filtros)),
        300,
        fn() => $repo->itensFaltantesRecorrentes(10, $filtros)
    ),
    'filtros_ativos' => $filtros,
];

Response::success('OK', $dados);
