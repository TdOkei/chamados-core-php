<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HistoricoRepository;

class ExportService
{
    private HistoricoRepository $repo;

    public function __construct()
    {
        $this->repo = new HistoricoRepository();
    }

    /**
     * Gera o arquivo Excel e retorna o caminho temporário.
     * Retorna null em caso de falha.
     */
    public function gerarExcelHistorico(array $filtros = []): ?string
    {
        $registros = $this->repo->listar($filtros, 2000);
        $totais    = $this->repo->totais($filtros);
        $logQr     = $this->repo->listarLogQr(
            array_intersect_key($filtros, array_flip(['data_inicio', 'data_fim'])),
            1000
        );

        // Enriquece com itens de produtos
        foreach ($registros as &$r) {
            if ($r['categoria'] === 'produto') {
                $r['itens'] = $this->repo->buscarItensSolicitacao((int) $r['id']);
            } else {
                $r['itens'] = [];
            }
        }

        // Monta filtros com label de escola se necessário
        $filtroExport = $filtros;
        if (!empty($filtros['id_escola'])) {
            $escolas = $this->repo->listarEscolas();
            foreach ($escolas as $e) {
                if ((int)$e['id'] === (int)$filtros['id_escola']) {
                    $filtroExport['escola'] = $e['nome'];
                    break;
                }
            }
        }

        $payload = [
            'registros' => $registros,
            'totais'    => $totais,
            'log_qr'    => $logQr,
            'filtros'   => $filtroExport,
        ];

        // Arquivos temporários
        $tmpDir    = sys_get_temp_dir();
        $inputFile = $tmpDir . '/fila_export_' . uniqid() . '.json';
        $outFile   = $tmpDir . '/fila_historico_' . date('Y-m-d_His') . '.xlsx';
        $scriptPath= __DIR__ . '/../scripts/gerar_excel.py';

        file_put_contents($inputFile, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $cmd    = escapeshellcmd("python3 {$scriptPath} {$inputFile} {$outFile}") . ' 2>&1';
        $output = shell_exec($cmd);
        @unlink($inputFile);

        if (!$output) {
            return null;
        }

        $result = json_decode($output, true);
        if (empty($result['success'])) {
            return null;
        }

        return file_exists($outFile) ? $outFile : null;
    }
}
