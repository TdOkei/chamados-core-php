<?php
/**
 * Server-Sent Events — Stream de notificações em tempo real
 * Substitui o polling de 60s do layout-footer.php
 *
 * Endpoint: GET /public/api/notificacoes/stream.php
 * Configurado no nginx com fastcgi_buffering off
 */

declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Helpers\Session;
use App\Repositories\NotificacaoRepository;
use App\Middleware\AuthMiddleware;

AuthMiddleware::check(isApi: true);

$idUsuario = Session::getUsuarioId();
$perfil    = Session::getPerfil();

// ---- Headers SSE ----
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');  // Desativa buffer do nginx para este response

// Desativa buffer do PHP
ini_set('output_buffering', '0');
ini_set('implicit_flush', '1');
if (ob_get_level()) ob_end_flush();

// ---- Configurações do loop ----
$intervalo      = 8;    // Segundos entre verificações
$maxTempo       = 3540; // 59 minutos (antes do timeout de sessão de 1h)
$inicio         = time();
$repo           = new NotificacaoRepository();
$ultimaContagem = -1;   // Força envio inicial

/**
 * Envia um evento SSE para o cliente.
 */
function enviarEvento(string $evento, array $dados): void
{
    $json = json_encode($dados, JSON_UNESCAPED_UNICODE);
    echo "event: {$evento}\n";
    echo "data: {$json}\n\n";
    flush();
}

/**
 * Envia heartbeat para manter a conexão viva.
 */
function enviarHeartbeat(): void
{
    echo ": heartbeat " . date('H:i:s') . "\n\n";
    flush();
}

// ---- Evento inicial de conexão ----
enviarEvento('conectado', ['message' => 'SSE conectado', 'usuario_id' => $idUsuario]);

// ---- Loop principal ----
while (true) {
    // Verifica se o cliente ainda está conectado
    if (connection_aborted()) {
        break;
    }

    // Encerra após tempo máximo (cliente reconecta automaticamente)
    if ((time() - $inicio) >= $maxTempo) {
        enviarEvento('reconectar', ['message' => 'Reconecte para manter a sessão ativa.']);
        break;
    }

    try {
        $naoLidas = $repo->contarNaoLidas($idUsuario);
        $badges   = $repo->contarBadges($idUsuario, $perfil);

        // Só envia se houve mudança — evita tráfego desnecessário
        if ($naoLidas !== $ultimaContagem) {
            $ultimaContagem = $naoLidas;

            // Busca as últimas notificações para mostrar no painel
            $notifs = $repo->listar($idUsuario, 5);
            foreach ($notifs as &$n) {
                $diff = time() - strtotime($n['created_at']);
                $n['tempo_relativo'] = match(true) {
                    $diff < 60    => 'Agora mesmo',
                    $diff < 3600  => (int)($diff / 60) . ' min atrás',
                    $diff < 86400 => (int)($diff / 3600) . ' h atrás',
                    default       => date('d/m/Y', time() - $diff),
                };
                $n['lida'] = (bool) $n['lida'];
            }

            enviarEvento('notificacoes', [
                'nao_lidas'    => $naoLidas,
                'badges'       => $badges,
                'notificacoes' => $notifs,
            ]);
        } else {
            // Envia heartbeat a cada ciclo mesmo sem mudança
            enviarHeartbeat();
        }
    } catch (\Throwable $e) {
        // Em caso de erro de banco, aguarda e tenta novamente
        enviarHeartbeat();
    }

    sleep($intervalo);
}
