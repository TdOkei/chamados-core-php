<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Services\ConfiguracaoService;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

AuthMiddleware::requireSuperadmin(isApi: true);

$svc = new ConfiguracaoService();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        Response::success('OK', $svc->obterRemessa());

    case 'POST':
        $r = $svc->salvarTextos((string) ($_POST['linha1'] ?? ''), (string) ($_POST['linha2'] ?? ''));
        if (!$r['success']) Response::error($r['message']);

        if (!empty($_POST['remover_logo'])) {
            $svc->removerLogo();
        } elseif (!empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? 0) !== UPLOAD_ERR_NO_FILE) {
            $r = $svc->salvarLogo($_FILES['logo']);
            if (!$r['success']) Response::error($r['message']);
        }
        Response::success('Configurações salvas.', $svc->obterRemessa());

    default:
        Response::error('Método não permitido.', 405);
}
