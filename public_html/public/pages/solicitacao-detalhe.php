<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Services\SolicitacaoService;
use App\Config\App;

AuthMiddleware::check();

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) { header('Location: /public/dashboard.php'); exit; }

$service     = new SolicitacaoService();
$solicitacao = $service->buscarPorId((int) $id);
if (!$solicitacao) { header('Location: /public/dashboard.php'); exit; }

$perfil    = Session::getPerfil();
$idUsuario = Session::getUsuarioId();
$idEscola  = Session::getIdEscola();
$categoria = Session::getCategoria();

// Diretor só vê sua própria escola
if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) && (int)$solicitacao['escola_id'] !== $idEscola) {
    header('Location: /public/dashboard.php'); exit;
}

$pageTitle  = 'Solicitação ' . '#' . str_pad((string)$solicitacao['id'], 4, '0', STR_PAD_LEFT);
$activePage = in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) ? 'solicitacoes' : 'fila';
$labels     = App::STATUS_LABELS;
$colors     = App::STATUS_COLORS;

// Formata status bruto caso não esteja no array de labels
function formatarStatus(string $status, array $labels): string {
    if (isset($labels[$status])) return $labels[$status];
    return ucwords(str_replace('_', ' ', $status));
}
$idPad      = '#' . str_pad((string)$solicitacao['id'], 4, '0', STR_PAD_LEFT);

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <!-- Breadcrumb -->
  <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-6);font-size:13px;color:var(--secondary);">
    <a href="/dashboard.php" style="color:var(--secondary);">Dashboard</a>
    <i data-lucide="chevron-right" width="14" height="14"></i>
    <?php if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)): ?>
      <a href="/pages/solicitacoes.php" style="color:var(--secondary);">Minhas Solicitações</a>
    <?php else: ?>
      <a href="/pages/fila.php" style="color:var(--secondary);">Fila de Pedidos</a>
    <?php endif; ?>
    <i data-lucide="chevron-right" width="14" height="14"></i>
    <span style="color:var(--primary);font-weight:var(--fw-semibold);"><?= $idPad ?></span>
  </div>

  <!-- Cabeçalho -->
  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:4px;">
        <h2 class="text-h2"><?= $idPad ?></h2>
        <span class="badge badge-<?= $solicitacao['categoria'] ?>" style="font-size:13px;">
          <?= $solicitacao['categoria'] === 'produto' ? 'Produto' : 'Serviço' ?>
        </span>
        <span class="badge <?= $colors[$solicitacao['status']] ?? '' ?>" style="font-size:13px;">
          <?= formatarStatus($solicitacao['status'], $labels) ?>
        </span>
      </div>
      <p style="font-size:13px;color:var(--secondary);">
        Criada em <?= date('d/m/Y \à\s H:i', strtotime($solicitacao['created_at'])) ?>
      </p>
    </div>

    <!-- Ações rápidas para admin/superadmin -->
    <?php if (!in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)): ?>
    <div class="d-flex gap-2 flex-wrap" id="botoesAcao">
      <?php
      $transicoesAdmin = App::TRANSICOES_VALIDAS['administrador'][$solicitacao['status']] ?? [];
      $labelsAcao = [
        'em_analise'       => ['search',   'Em Análise',       'btn-outline'],
        'em_processamento' => ['settings', 'Em Processamento', 'btn-outline'],
        'despachado'       => ['truck',    'Despachar',        'btn-primary'],
        'pendente'         => ['undo-2',   'Pendente',         'btn-outline'],
        'rejeitado'        => ['x-circle', 'Rejeitar',         'btn-danger'],
      ];
      foreach ($transicoesAdmin as $acao):
        $cfg = $labelsAcao[$acao] ?? ['arrow-right', ucfirst($acao), 'btn-outline'];
        if ($acao === 'rejeitado'): ?>
          <button class="btn <?= $cfg[2] ?> btn-sm" onclick="abrirRejeitar()">
            <i data-lucide="<?= $cfg[0] ?>" width="14" height="14"></i> <?= $cfg[1] ?>
          </button>
        <?php else: ?>
          <button class="btn <?= $cfg[2] ?> btn-sm" onclick="mudarStatus('<?= $acao ?>')">
            <i data-lucide="<?= $cfg[0] ?>" width="14" height="14"></i> <?= $cfg[1] ?>
          </button>
        <?php endif;
      endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Cancelar (diretor, pendente) -->
    <?php if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) && $solicitacao['status'] === App::STATUS_PENDENTE): ?>
    <button class="btn btn-outline btn-sm" style="border-color:var(--brand);color:var(--brand);"
            onclick="cancelar()">
      <i data-lucide="x-circle" width="14" height="14"></i> Cancelar
    </button>
    <?php endif; ?>
  </div>

  <div class="grid-2" style="gap:var(--space-5);align-items:start;">

    <!-- Coluna principal -->
    <div style="display:flex;flex-direction:column;gap:var(--space-5);">

      <!-- Informações gerais -->
      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Informações</h3>
        </div>
        <div style="display:flex;flex-direction:column;gap:var(--space-3);">
          <?php
          $info = [
            ['Escola',    $solicitacao['escola_nome']],
            ['Diretor(a)',$solicitacao['diretor_nome']],
            ['Categoria', $solicitacao['categoria'] === 'produto' ? 'Produto' : 'Serviço'],
            ['Status',    null], // badge especial
          ];
          if ($solicitacao['admin_nome']) $info[] = ['Responsável', $solicitacao['admin_nome']];
          if ($solicitacao['nome_recebedor']) $info[] = ['Recebido por', $solicitacao['nome_recebedor']];
          if ($solicitacao['motivo_rejeicao']) $info[] = ['Motivo da rejeição', $solicitacao['motivo_rejeicao']];
          if ($solicitacao['obs_item_falta']) $info[] = ['Item em falta', $solicitacao['obs_item_falta']];
          ?>
          <?php foreach ($info as [$label, $val]): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--space-2) 0;border-bottom:1px solid var(--neutral-100);">
            <span style="font-size:13px;color:var(--secondary);"><?= $label ?></span>
            <?php if ($label === 'Status'): ?>
              <span class="badge <?= $colors[$solicitacao['status']] ?? '' ?>"><?= formatarStatus($solicitacao['status'], $labels) ?></span>
            <?php elseif ($label === 'Motivo da rejeição' || $label === 'Item em falta'): ?>
              <span style="font-size:13px;color:var(--brand);max-width:200px;text-align:right;"><?= htmlspecialchars($val) ?></span>
            <?php else: ?>
              <span style="font-size:14px;font-weight:var(--fw-medium);"><?= htmlspecialchars($val ?? '—') ?></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Itens do pedido -->
      <?php if ($solicitacao['categoria'] === 'produto' && !empty($solicitacao['itens'])):
        $itensEntregues = $solicitacao['itens_entregues'] ?? [];
        $totalEntregues = count($itensEntregues);
      ?>
      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Itens Solicitados</h3>
          <div style="display:flex;align-items:center;gap:var(--space-3);">
            <?php if ($totalEntregues > 0): ?>
            <span style="font-size:11px;color:#16a34a;font-weight:var(--fw-semibold);">
              ✓ <?= $totalEntregues ?> já entregue<?= $totalEntregues !== 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
            <span style="font-size:12px;color:var(--secondary);"><?= count($solicitacao['itens']) ?> item<?= count($solicitacao['itens']) !== 1 ? 's' : '' ?></span>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:var(--space-2);">
          <?php foreach ($solicitacao['itens'] as $i => $item):
            $jaEntregue = in_array($item['id'], array_map('intval', $itensEntregues));
          ?>
          <div style="display:flex;justify-content:space-between;align-items:center;
                      padding:var(--space-3);background:var(--neutral-100);border-radius:var(--radius-md);
                      <?= $jaEntregue ? 'opacity:0.6;' : '' ?>">
            <div style="display:flex;align-items:center;gap:var(--space-3);">
              <div style="width:28px;height:28px;border-radius:50%;background:var(--white);
                          display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:var(--fw-bold);color:var(--secondary);">
                <?= $i + 1 ?>
              </div>
              <span style="font-size:14px;font-weight:var(--fw-medium);<?= $jaEntregue ? 'text-decoration:line-through;color:var(--secondary);' : '' ?>">
                <?= htmlspecialchars($item['produto_nome']) ?>
              </span>
            </div>
            <div style="display:flex;align-items:center;gap:var(--space-2);">
              <span class="badge <?= $jaEntregue ? '' : 'badge-analysis' ?>"
                    style="<?= $jaEntregue ? 'background:#dcfce7;color:#16a34a;' : '' ?>">
                <?= number_format((float)$item['quantidade'], 2, ',', '.') ?> <?= htmlspecialchars($item['unidade_sigla']) ?>
              </span>
              <?php if ($jaEntregue): ?>
              <span style="font-size:11px;background:#dcfce7;color:#16a34a;padding:2px 10px;border-radius:50px;font-weight:600;">Entregue</span>
              <?php elseif ($totalEntregues > 0): ?>
              <span style="font-size:11px;background:#fce7f3;color:#be123c;padding:2px 10px;border-radius:50px;font-weight:600;">Pendente</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Descrição do serviço -->
      <?php if ($solicitacao['categoria'] === 'servico'): ?>
      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Descrição do Serviço</h3>
        </div>
        <p style="font-size:14px;line-height:1.7;color:var(--primary);">
          <?= nl2br(htmlspecialchars($solicitacao['descricao_servico'])) ?>
        </p>
        <?php if (!empty($solicitacao['foto_servico'])): ?>
        <div style="margin-top:var(--space-4);">
          <div style="font-size:12px;font-weight:var(--fw-semibold);color:var(--secondary);margin-bottom:var(--space-2);">
            <i data-lucide="camera" width="13" height="13" style="display:inline;vertical-align:middle;"></i>
            Foto anexada
          </div>
          <a href="/api/upload/imagem.php?arquivo=<?= urlencode($solicitacao['foto_servico']) ?>"
             target="_blank" rel="noopener">
            <img src="/api/upload/imagem.php?arquivo=<?= urlencode($solicitacao['foto_servico']) ?>"
                 alt="Foto do serviço"
                 style="width:100%;max-height:350px;object-fit:cover;border-radius:var(--radius-lg);
                        cursor:pointer;transition:var(--transition);"
                 onmouseover="this.style.opacity='0.85'"
                 onmouseout="this.style.opacity='1'">
          </a>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>

    <!-- Coluna lateral -->
    <div style="display:flex;flex-direction:column;gap:var(--space-5);">

      <?php if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)): ?>

      <?php
      $etapas = [
        ['status' => App::STATUS_PENDENTE,        'label' => 'Pendente',         'icon' => 'clock'],
        ['status' => App::STATUS_EM_ANALISE,       'label' => 'Em Análise',       'icon' => 'search'],
        ['status' => App::STATUS_EM_PROCESSAMENTO, 'label' => 'Em Processamento', 'icon' => 'settings'],
        ['status' => App::STATUS_DESPACHADO,       'label' => 'Despachado',       'icon' => 'truck'],
        ['status' => App::STATUS_CONCLUIDO,        'label' => 'Concluído',        'icon' => 'check-circle'],
      ];
      $statusDesvio = [
        App::STATUS_CANCELADO     => ['label' => 'Cancelado',    'icon' => 'x-circle', 'color' => 'var(--secondary)'],
        App::STATUS_REJEITADO     => ['label' => 'Rejeitado',    'icon' => 'x-circle', 'color' => 'var(--brand)'],
        App::STATUS_RETORNADO     => ['label' => 'Retornado',    'icon' => 'undo-2',   'color' => '#f59e0b'],
        App::STATUS_ITEM_EM_FALTA => ['label' => 'Item em Falta','icon' => 'package-x','color' => '#f59e0b'],
      ];
      $statusAtual = $solicitacao['status'];
      $statusesNoHistorico = array_column($solicitacao['historico'] ?? [], 'status_novo');
      $indiceAtivo = -1;
      foreach ($etapas as $i => $etapa) {
        if (in_array($etapa['status'], $statusesNoHistorico) || $etapa['status'] === $statusAtual) {
          $indiceAtivo = $i;
        }
      }
      $obsSecretaria = array_filter(
        $solicitacao['historico'] ?? [],
          fn($h) => !empty($h['observacao']) && !in_array($h['usuario_perfil'], App::PERFIS_OPERACIONAIS_ESCOLA, true)
      );
      ?>

      <!-- Andamento -->
      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Andamento</h3>
          <span class="badge <?= $colors[$statusAtual] ?? '' ?>"><?= formatarStatus($statusAtual, $labels) ?></span>
        </div>

        <?php if (isset($statusDesvio[$statusAtual])): ?>
          <?php $desvio = $statusDesvio[$statusAtual]; ?>
          <div style="display:flex;flex-direction:column;gap:var(--space-1);margin-bottom:var(--space-4);">
            <?php foreach ($etapas as $i => $etapa):
              $passou = $i <= $indiceAtivo; ?>
            <div style="display:flex;align-items:center;gap:var(--space-3);">
              <div style="width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:<?= $passou ? 'var(--brand)' : 'var(--neutral-200)' ?>;box-shadow:<?= $passou ? 'var(--shadow-brand)' : 'none' ?>;">
                <i data-lucide="<?= $etapa['icon'] ?>" width="13" height="13" style="color:<?= $passou ? 'var(--white)' : 'var(--neutral-300)' ?>;"></i>
              </div>
              <span style="font-size:13px;font-weight:<?= $passou ? 'var(--fw-semibold)' : 'var(--fw-regular)' ?>;color:<?= $passou ? 'var(--primary)' : 'var(--neutral-300)' ?>;"><?= $etapa['label'] ?></span>
            </div>
            <?php if ($i < count($etapas) - 1): ?>
            <div style="width:2px;height:16px;background:<?= $i < $indiceAtivo ? 'var(--brand)' : 'var(--neutral-200)' ?>;margin-left:13px;border-radius:2px;"></div>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3);border-radius:var(--radius-md);background:var(--neutral-100);border:1px solid var(--neutral-200);">
            <div style="width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:<?= $desvio['color'] ?>;">
              <i data-lucide="<?= $desvio['icon'] ?>" width="13" height="13" style="color:var(--white);"></i>
            </div>
            <span style="font-size:13px;font-weight:var(--fw-semibold);color:<?= $desvio['color'] ?>;"><?= $desvio['label'] ?></span>
          </div>

        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:var(--space-1);">
            <?php foreach ($etapas as $i => $etapa):
              $passou  = in_array($etapa['status'], $statusesNoHistorico) || $etapa['status'] === $statusAtual;
              $eAtual  = $etapa['status'] === $statusAtual; ?>
            <div style="display:flex;align-items:center;gap:var(--space-3);">
              <div style="width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:<?= $passou ? 'var(--brand)' : 'var(--neutral-200)' ?>;box-shadow:<?= $eAtual ? 'var(--shadow-brand)' : 'none' ?>;<?= $eAtual ? 'outline:3px solid var(--brand-light);' : '' ?>">
                <i data-lucide="<?= $etapa['icon'] ?>" width="13" height="13" style="color:<?= $passou ? 'var(--white)' : 'var(--neutral-300)' ?>;"></i>
              </div>
              <div>
                <span style="font-size:13px;font-weight:<?= $passou ? 'var(--fw-semibold)' : 'var(--fw-regular)' ?>;color:<?= $passou ? 'var(--primary)' : 'var(--neutral-300)' ?>;"><?= $etapa['label'] ?></span>
                <?php if ($eAtual): ?>
                <div style="font-size:11px;color:var(--brand);font-weight:var(--fw-medium);">Status atual</div>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($i < count($etapas) - 1): ?>
            <div style="width:2px;height:16px;background:<?= $i < $indiceAtivo ? 'var(--brand)' : 'var(--neutral-200)' ?>;margin-left:13px;border-radius:2px;"></div>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Observações da Secretaria -->
      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Observações</h3>
          <span style="font-size:11px;color:var(--secondary);">da Secretaria</span>
        </div>
        <?php if (empty($obsSecretaria)): ?>
          <div class="empty-state" style="padding:var(--space-5) 0;">
            <i data-lucide="message-square" width="28" height="28" style="color:var(--neutral-300);margin:0 auto;"></i>
            <p style="font-size:13px;color:var(--secondary);margin-top:var(--space-2);">Nenhuma observação registrada.</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:var(--space-3);">
            <?php foreach ($obsSecretaria as $obs): ?>
            <div style="padding:var(--space-3);background:var(--neutral-100);border-radius:var(--radius-md);border-left:3px solid var(--brand);">
              <p style="font-size:13px;color:var(--primary);line-height:1.6;margin:0;"><?= htmlspecialchars($obs['observacao']) ?></p>
              <div style="font-size:11px;color:var(--secondary);margin-top:var(--space-2);"><?= date('d/m/Y H:i', strtotime($obs['created_at'])) ?> &middot; <?= htmlspecialchars(formatarStatus($obs['status_novo'], $labels)) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php else: ?>

      <!-- QR Code (admin e superadmin) -->
      <div class="card" style="border-radius:var(--radius-xl);text-align:center;">
        <div class="card-header">
          <h3 class="card-title">QR Code</h3>
          <?php if ($solicitacao['status'] === App::STATUS_DESPACHADO): ?>
          <span class="badge badge-dispatched animate-pulse-brand">Aguardando leitura</span>
          <?php elseif ($solicitacao['qr_invalidado']): ?>
          <span class="badge badge-cancelled">Invalidado</span>
          <?php endif; ?>
        </div>
        <?php if ($solicitacao['qr_invalidado']): ?>
          <div class="empty-state" style="padding:var(--space-6);">
            <i data-lucide="qr-code" width="32" height="32" style="color:var(--neutral-300);margin:0 auto;"></i>
            <p style="font-size:13px;color:var(--secondary);">QR Code invalidado após conclusão.</p>
            <?php if (\App\Helpers\Session::isAdmin() || \App\Helpers\Session::isSuperadmin()): ?>
            <a href="/pages/remessa.php?id=<?= (int) $solicitacao['id'] ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="margin-top:8px;">Reimprimir Remessa</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php
          $qrUrl = $service->gerarQrUrl($solicitacao['qr_token']);
          ?>
          <div style="display:flex;flex-direction:column;align-items:center;gap:var(--space-3);padding:var(--space-2);">
            <div style="padding:var(--space-3);background:var(--white);border:3px solid var(--neutral-100);border-radius:var(--radius-lg);">
              <img id="qrImgLocal" data-qr="<?= htmlspecialchars($qrUrl, ENT_QUOTES) ?>" src="" alt="QR Code" style="width:160px;height:160px;display:block;">
            </div>
            <p style="font-size:12px;color:var(--secondary);text-align:center;max-width:200px;">
              <?php if ($solicitacao['status'] === App::STATUS_DESPACHADO): ?>
                O diretor deve escanear este código ao receber o pedido.
              <?php elseif (in_array($solicitacao['status'], [App::STATUS_CONCLUIDO, App::STATUS_CANCELADO, App::STATUS_REJEITADO])): ?>
                Solicitação encerrada.
              <?php else: ?>
                Disponível para uso quando o pedido for despachado.
              <?php endif; ?>
            </p>
            <?php if ((\App\Helpers\Session::isAdmin() || \App\Helpers\Session::isSuperadmin())
                    && in_array($solicitacao['status'], [App::STATUS_DESPACHADO, App::STATUS_ITEM_EM_FALTA], true)): ?>
            <a href="/pages/remessa.php?id=<?= (int) $solicitacao['id'] ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
              <i data-lucide="printer" width="14" height="14"></i> Imprimir Remessa
            </a>
            <?php endif; ?>
            <a id="qrDownloadLocal" href="#" download="qr-<?= $solicitacao['id'] ?>.png" class="btn btn-outline btn-sm">
              <i data-lucide="download" width="14" height="14"></i> Salvar QR Code
            </a>
          </div>
        <?php endif; ?>
      </div>

      <?php endif; ?>

      <?php if (!empty($solicitacao['foto_conclusao'])): ?>
      <div class="card" style="padding:var(--space-5);margin-bottom:var(--space-4);">
        <h3 class="card-title" style="margin-bottom:var(--space-3);">Foto do serviço concluído</h3>
        <a href="/api/upload/imagem.php?arquivo=<?= urlencode($solicitacao['foto_conclusao']) ?>" target="_blank" rel="noopener">
          <img src="/api/upload/imagem.php?arquivo=<?= urlencode($solicitacao['foto_conclusao']) ?>" alt="Foto do serviço concluído"
               style="width:100%;border-radius:var(--radius-lg);display:block;">
        </a>
      </div>
      <?php endif; ?>

      <!-- Histórico de status -->
      <?php if (!empty($solicitacao['historico'])): ?>
      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Histórico</h3>
        </div>
        <div style="display:flex;flex-direction:column;gap:var(--space-4);position:relative;">
          <!-- Linha vertical -->
          <div style="position:absolute;left:11px;top:8px;bottom:8px;width:2px;background:var(--neutral-200);border-radius:2px;"></div>
          <?php foreach ($solicitacao['historico'] as $h): ?>
          <div style="display:flex;gap:var(--space-3);align-items:flex-start;position:relative;">
            <div style="width:24px;height:24px;border-radius:50%;background:var(--brand);flex-shrink:0;
                        display:flex;align-items:center;justify-content:center;border:3px solid var(--white);
                        box-shadow:var(--shadow-sm);z-index:1;">
              <div style="width:6px;height:6px;border-radius:50%;background:var(--white);"></div>
            </div>
            <div style="flex:1;padding-top:2px;">
              <div style="font-size:13px;font-weight:var(--fw-semibold);">
                <?= htmlspecialchars(formatarStatus($h['status_novo'], $labels)) ?>
              </div>
              <div style="font-size:11px;color:var(--secondary);margin-top:2px;">
                <?= htmlspecialchars($h['usuario_nome']) ?> · <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
              </div>
              <?php if ($h['observacao']): ?>
              <div style="font-size:12px;color:var(--secondary);font-style:italic;margin-top:3px;">
                <?= htmlspecialchars($h['observacao']) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Comentarios -->
      <?php
        $podecomentar = in_array($perfil, [App::PERFIL_ADMINISTRADOR, App::PERFIL_SUPERADMIN, App::PERFIL_DIRETOR, App::PERFIL_ESCRITURARIO, App::PERFIL_ESTAGIARIO], true);
        $statusFechado = in_array($solicitacao['status'], [App::STATUS_CONCLUIDO, App::STATUS_CANCELADO, App::STATUS_REJEITADO]);
      ?>
      <?php if ($podecomentar): ?>
      <div class="card" style="border-radius:var(--radius-xl);" id="cardComentarios">
        <div class="card-header">
          <h3 class="card-title">
            <i data-lucide="message-circle" width="16" height="16" style="display:inline;vertical-align:middle;margin-right:6px;"></i>
            Comentarios
          </h3>
          <span id="badgeComentarios" style="font-size:12px;color:var(--secondary);"></span>
        </div>

        <!-- Lista de comentarios -->
        <div id="listaComentarios" style="display:flex;flex-direction:column;gap:var(--space-3);margin-bottom:var(--space-4);">
          <?php if (empty($solicitacao['comentarios'])): ?>
          <div class="empty-state" style="padding:var(--space-5) 0;" id="emptyComentarios">
            <i data-lucide="message-circle" width="28" height="28" style="color:var(--neutral-300);margin:0 auto;"></i>
            <p style="font-size:13px;color:var(--secondary);margin-top:var(--space-2);">Nenhum comentario ainda.</p>
          </div>
          <?php else: ?>
          <?php foreach ($solicitacao['comentarios'] as $c):
            $isMe        = (int)$c['id_usuario'] === $idUsuario;
            $isSuperadmin = $c['perfil'] === 'superadmin';
          ?>
          <?php if ($isSuperadmin && !$isMe): ?>
          <!-- Superadmin: centralizado com balao preto -->
          <div style="display:flex;flex-direction:column;gap:2px;align-items:center;">
            <div style="font-size:11px;color:var(--secondary);">
              <?= htmlspecialchars($c['usuario_nome']) ?> · <?= date('d/m H:i', strtotime($c['created_at'])) ?>
            </div>
            <div style="max-width:80%;padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);
                        background:var(--primary);color:var(--white);
                        font-size:13px;line-height:1.6;text-align:center;">
              <?= nl2br(htmlspecialchars($c['mensagem'])) ?>
            </div>
            <div style="font-size:10px;color:var(--secondary);">
              <i data-lucide="shield" width="10" height="10" style="display:inline;vertical-align:middle;"></i> Administrador do Sistema
            </div>
          </div>
          <?php elseif ($isMe): ?>
          <!-- Minha mensagem: alinhada a direita -->
          <div style="display:flex;flex-direction:column;gap:2px;align-items:flex-end;">
            <div style="font-size:11px;color:var(--secondary);margin-right:var(--space-2);">
              <?= htmlspecialchars($c['usuario_nome']) ?> · <?= date('d/m H:i', strtotime($c['created_at'])) ?>
            </div>
            <div style="max-width:80%;padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);
                        background:var(--brand);color:var(--white);
                        border-bottom-right-radius:4px;font-size:13px;line-height:1.6;">
              <?= nl2br(htmlspecialchars($c['mensagem'])) ?>
            </div>
          </div>
          <?php else: ?>
          <!-- Outra pessoa: alinhada a esquerda -->
          <div style="display:flex;flex-direction:column;gap:2px;align-items:flex-start;">
            <div style="font-size:11px;color:var(--secondary);margin-left:var(--space-2);">
              <?= htmlspecialchars($c['usuario_nome']) ?> · <?= date('d/m H:i', strtotime($c['created_at'])) ?>
            </div>
            <div style="max-width:80%;padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);
                        background:var(--neutral-100);color:var(--primary);
                        border-bottom-left-radius:4px;font-size:13px;line-height:1.6;">
              <?= nl2br(htmlspecialchars($c['mensagem'])) ?>
            </div>
            <?php if (in_array($c['perfil'], ['administrador'])): ?>
            <div style="font-size:10px;color:var(--secondary);margin-left:var(--space-2);">
              <i data-lucide="shield" width="10" height="10" style="display:inline;vertical-align:middle;"></i> Secretaria
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Campo de envio -->
        <?php if (!$statusFechado): ?>
        <div style="display:flex;gap:var(--space-2);align-items:flex-end;border-top:1px solid var(--neutral-200);padding-top:var(--space-4);">
          <textarea id="inputComentario" class="form-control" rows="2"
                    placeholder="Escreva um comentario..."
                    maxlength="1000"
                    style="flex:1;resize:none;"
                    onkeydown="if(event.ctrlKey&&event.key==='Enter')enviarComentario()"></textarea>
          <button class="btn btn-primary" onclick="enviarComentario()" id="btnComentario"
                  style="height:42px;padding:0 var(--space-4);">
            <i data-lucide="send" width="16" height="16"></i>
          </button>
        </div>
        <div style="font-size:11px;color:var(--secondary);margin-top:var(--space-2);">
          Ctrl+Enter para enviar
        </div>
        <?php else: ?>
        <p style="font-size:12px;color:var(--secondary);text-align:center;padding:var(--space-3) 0;">
          Solicitacao encerrada — comentarios desabilitados.
        </p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Modal rejeitar -->
<div class="modal-overlay" id="modalRejeitar" style="display:none;" onclick="if(event.target===this)fecharRejeitar()">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <h3 class="modal-title">Rejeitar Solicitação</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharRejeitar()"><i data-lucide="x" width="18" height="18"></i></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning mb-4"><i data-lucide="alert-triangle" width="16" height="16"></i> Esta ação não pode ser desfeita.</div>
      <div class="form-group">
        <label class="form-label">Motivo <span class="required">*</span></label>
        <textarea id="motivoRejeicao" class="form-control" rows="3" maxlength="300"
                  placeholder="Descreva o motivo da rejeição..."
                  data-maxlength="300" data-counter="cntMotivo"></textarea>
        <div class="char-counter" id="cntMotivo">0/300</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharRejeitar()">Cancelar</button>
      <button class="btn btn-danger" id="btnConfirmarRejeitar" onclick="confirmarRejeitar()">
        <i data-lucide="x-circle" width="14" height="14"></i> Confirmar rejeição
      </button>
    </div>
  </div>
</div>

<script src="/assets/js/qrcode.js"></script>
<script src="/assets/js/qr-local.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var img = document.getElementById('qrImgLocal');
  if (!img) return;
  var png = tdQrDataUrl(img.dataset.qr, 400);
  img.src = png;
  var a = document.getElementById('qrDownloadLocal');
  if (a) a.href = png;
});
</script>
<script>
const solId       = <?= $solicitacao['id'] ?>;
const solCategoria= '<?= $solicitacao['categoria'] ?>';

async function mudarStatus(novoStatus) {
  const btn = event.target.closest('button');
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span>'; }

  try {
    const res  = await fetch(`/api/solicitacoes/index.php?id=${solId}&acao=status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: novoStatus, categoria_override: solCategoria }),
    });
    const data = await res.json();

    if (data.success) {
      if (novoStatus === 'despachado') sessionStorage.setItem('showQrAlert', '1');
      location.reload();
    } else {
      alert(data.message);
      if (btn) { btn.disabled = false; }
    }
  } catch(e) { alert('Erro de conexão.'); if (btn) btn.disabled = false; }
}

function abrirRejeitar()  { document.getElementById('modalRejeitar').style.display = 'flex'; document.getElementById('motivoRejeicao').dispatchEvent(new Event('input')); }
function fecharRejeitar() { document.getElementById('modalRejeitar').style.display = 'none'; }

async function confirmarRejeitar() {
  const motivo = document.getElementById('motivoRejeicao').value.trim();
  if (!motivo) return alert('Informe o motivo.');

  const btn = document.getElementById('btnConfirmarRejeitar');
  btn.disabled = true;
  try {
    const res  = await fetch(`/api/solicitacoes/index.php?id=${solId}&acao=status`, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ status: 'rejeitado', motivo_rejeicao: motivo, categoria_override: solCategoria }),
    });
    const data = await res.json();
    if (data.success) location.reload();
    else { alert(data.message); btn.disabled = false; }
  } catch(e) { alert('Erro.'); btn.disabled = false; }
}

async function cancelar() {
  if (!confirm('Confirma o cancelamento desta solicitação?')) return;
  const res  = await fetch(`/api/solicitacoes/index.php?id=${solId}&acao=cancelar`, { method: 'POST' });
  const data = await res.json();
  if (data.success) location.reload();
  else alert(data.message);
}

// Alerta ao voltar de despacho
if (sessionStorage.getItem('showQrAlert')) {
  sessionStorage.removeItem('showQrAlert');
  const alert = document.createElement('div');
  alert.className = 'alert alert-info animate-slide-up';
  alert.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;max-width:340px;box-shadow:var(--shadow-xl);';
  alert.innerHTML = '<i data-lucide="qr-code" width="16" height="16"></i> Pedido despachado! Salve o QR Code para a entrega.';
  document.body.appendChild(alert);
  if (window.lucide) lucide.createIcons();
  setTimeout(() => alert.remove(), 5000);
}

async function enviarComentario() {
  const input = document.getElementById('inputComentario');
  const btn   = document.getElementById('btnComentario');
  if (!input || !btn) return;

  const mensagem = input.value.trim();
  if (!mensagem) { input.focus(); return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;border-top-color:var(--white);"></span>';

  try {
    const res  = await fetch('/api/comentarios/index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_solicitacao: <?= (int) $solicitacao['id'] ?>,
        mensagem,
      }),
    });
    const data = await res.json();

    if (!data.success) {
      alert(data.message || 'Erro ao enviar comentario.');
      return;
    }

    const c       = data.data.comentario;
    const isMe    = true;
    const isAdmin = ['administrador','superadmin'].includes(c.perfil);
    const dt      = new Date(c.created_at.replace(' ','T'));
    const dtStr   = dt.toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'}) + ' ' + dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});

    // Remove empty state se existir
    const empty = document.getElementById('emptyComentarios');
    if (empty) empty.remove();

    const lista = document.getElementById('listaComentarios');
    const div   = document.createElement('div');
    div.style.cssText = 'display:flex;flex-direction:column;gap:2px;align-items:flex-end;';
    div.innerHTML = `
      <div style="font-size:11px;color:var(--secondary);margin-right:var(--space-2);">
        ${c.usuario_nome} &middot; ${dtStr}
      </div>
      <div style="max-width:80%;padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);
                  background:var(--brand);color:var(--white);
                  border-bottom-right-radius:4px;font-size:13px;line-height:1.6;">
        ${c.mensagem.split('\n').join('<br>')}
      </div>`;
    lista.appendChild(div);
    lista.scrollTop = lista.scrollHeight;

    // Atualiza badge
    const badge = document.getElementById('badgeComentarios');
    if (badge) {
      const atual = parseInt(badge.textContent) || 0;
      badge.textContent = (atual + 1) + ' comentario(s)';
    }

    input.value = '';
    if (window.lucide) lucide.createIcons();

  } catch(e) {
    alert('Erro de conexao. Tente novamente.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="send" width="16" height="16"></i>';
    if (window.lucide) lucide.createIcons();
  }
}

// Atualiza badge de comentarios no carregamento
(function() {
  const lista = document.getElementById('listaComentarios');
  const badge = document.getElementById('badgeComentarios');
  if (!lista || !badge) return;
  const total = lista.querySelectorAll('[style*="flex-direction:column"]').length;
  if (total > 0) badge.textContent = total + ' comentario(s)';
})();
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
