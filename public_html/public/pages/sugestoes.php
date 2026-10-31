<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Services\SuperadminService;

AuthMiddleware::requireSuperadmin();

$pageTitle  = 'Sugestões de Produtos';
$activePage = 'sugestoes';
$service    = new SuperadminService();
$pendentes  = $service->listarSugestoes('pendente');
$analisadas = $service->listarSugestoes('analisada');
$rejeitadas = $service->listarSugestoes('rejeitada');

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="mb-8">
    <h2 class="text-h2">Sugestões de Produtos</h2>
    <p class="text-body" style="color:var(--secondary);margin-top:4px;">
      Solicitações de novos produtos enviadas pelos diretores das escolas.
    </p>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:4px;background:var(--neutral-100);border-radius:var(--radius-lg);padding:4px;margin-bottom:var(--space-6);width:fit-content;">
    <button class="tab-btn active" onclick="mudarTab('pendente',this)">
      Pendentes
      <?php if (count($pendentes) > 0): ?>
        <span class="nav-badge" style="position:static;display:inline-flex;margin-left:6px;"><?= count($pendentes) ?></span>
      <?php endif; ?>
    </button>
    <button class="tab-btn" onclick="mudarTab('analisada',this)">Analisadas</button>
    <button class="tab-btn" onclick="mudarTab('rejeitada',this)">Rejeitadas</button>
  </div>

  <!-- Pendentes -->
  <div id="tab-pendente" class="tab-content">
    <?php if (empty($pendentes)): ?>
      <div class="card-flat" style="border-radius:var(--radius-xl);">
        <div class="empty-state" style="padding:var(--space-10);">
          <div class="empty-state-icon"><i data-lucide="check-circle" width="28" height="28"></i></div>
          <p class="empty-state-title">Tudo em dia!</p>
          <p class="empty-state-text">Nenhuma sugestão pendente no momento.</p>
        </div>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:var(--space-4);" class="stagger">
        <?php foreach ($pendentes as $s): ?>
        <div class="card animate-slide-up" style="border-radius:var(--radius-xl);">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:var(--space-4);flex-wrap:wrap;">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-3);">
                <div class="user-avatar" style="width:36px;height:36px;font-size:13px;flex-shrink:0;background:var(--neutral-800);">
                  <?= strtoupper(substr($s['escola_nome'], 0, 2)) ?>
                </div>
                <div>
                  <div style="font-weight:var(--fw-semibold);font-size:14px;"><?= htmlspecialchars($s['escola_nome']) ?></div>
                  <div style="font-size:12px;color:var(--secondary);">
                    por <?= htmlspecialchars($s['diretor_nome']) ?> · <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>
                  </div>
                </div>
              </div>
              <p style="font-size:14px;color:var(--primary);line-height:1.6;padding:var(--space-3) var(--space-4);background:var(--neutral-100);border-radius:var(--radius-md);">
                <?= nl2br(htmlspecialchars($s['descricao'])) ?>
              </p>
              <?php if (!empty($s['link'])):
                $host = parse_url($s['link'], PHP_URL_HOST) ?? '';
                $host = preg_replace('/^www\./', '', $host);
                $siteLabels = [
                  'mercadolivre.com.br' => ['Mercado Livre', 'shopping-cart'],
                  'mercadolibre.com.br' => ['Mercado Livre', 'shopping-cart'],
                  'amazon.com.br'       => ['Amazon',        'package'],
                  'shopee.com.br'       => ['Shopee',        'tag'],
                  'magazineluiza.com.br'=> ['Magazine Luiza','store'],
                  'americanas.com.br'   => ['Americanas',    'store'],
                  'casasbahia.com.br'   => ['Casas Bahia',   'store'],
                ];
                $found = false;
                foreach ($siteLabels as $domain => $info) {
                  if (str_contains($host, $domain)) {
                    [$siteName, $siteIcon] = $info;
                    $found = true;
                    break;
                  }
                }
                if (!$found) { $siteName = $host ?: 'Ver referencia'; $siteIcon = 'link'; }
              ?>
              <div style="padding:var(--space-2) var(--space-4);">
                <a href="<?= htmlspecialchars($s['link']) ?>" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:var(--space-2);
                          padding:var(--space-2) var(--space-3);border-radius:50px;
                          background:var(--neutral-100);border:1px solid var(--neutral-200);
                          font-size:12px;font-weight:var(--fw-medium);color:var(--brand);
                          text-decoration:none;transition:var(--transition);"
                   onmouseover="this.style.background='var(--brand-light)'"
                   onmouseout="this.style.background='var(--neutral-100)'">
                  <i data-lucide="<?= $siteIcon ?>" width="13" height="13"></i>
                  <?= htmlspecialchars($siteName) ?>
                  <i data-lucide="external-link" width="11" height="11" style="opacity:0.6;"></i>
                </a>
              </div>
              <?php endif; ?>
            </div>
            <div style="display:flex;flex-direction:column;gap:var(--space-2);flex-shrink:0;">
              <button class="btn btn-primary btn-sm" onclick="analisar(<?= $s['id'] ?>, 'analisada')">
                <i data-lucide="check" width="14" height="14"></i> Marcar analisada
              </button>
              <button class="btn btn-outline btn-sm" onclick="analisar(<?= $s['id'] ?>, 'rejeitada')"
                      style="border-color:var(--brand);color:var(--brand);">
                <i data-lucide="x" width="14" height="14"></i> Rejeitar
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Analisadas -->
  <div id="tab-analisada" class="tab-content" style="display:none;">
    <?= renderListaSugestoes($analisadas, 'badge-done', 'Analisada') ?>
  </div>

  <!-- Rejeitadas -->
  <div id="tab-rejeitada" class="tab-content" style="display:none;">
    <?= renderListaSugestoes($rejeitadas, 'badge-cancelled', 'Rejeitada') ?>
  </div>
</div>

<style>
.tab-btn {
  background: none; border: none; padding: 8px 16px;
  font-family: var(--font-primary); font-size: 13px; font-weight: var(--fw-semibold);
  color: var(--secondary); border-radius: var(--radius-md); cursor: pointer;
  transition: var(--transition); display:flex; align-items:center;
}
.tab-btn.active { background: var(--white); color: var(--primary); box-shadow: var(--shadow-sm); }
.tab-btn:hover:not(.active) { color: var(--primary); }
</style>

<?php
function renderListaSugestoes(array $lista, string $badgeClass, string $label): string {
    if (empty($lista)) {
        return '<div class="card-flat" style="border-radius:var(--radius-xl);"><div class="empty-state" style="padding:var(--space-10);"><p class="empty-state-text">Nenhuma sugestão aqui.</p></div></div>';
    }
    $html = '<div class="table-wrapper"><table class="table"><thead><tr><th>Escola</th><th>Diretor(a)</th><th>Descrição</th><th>Data</th><th>Status</th></tr></thead><tbody>';
    foreach ($lista as $s) {
        $html .= "<tr>
            <td style='font-weight:var(--fw-semibold);'>" . htmlspecialchars($s['escola_nome']) . "</td>
            <td style='font-size:13px;color:var(--secondary);'>" . htmlspecialchars($s['diretor_nome']) . "</td>
            <td style='font-size:13px;max-width:300px;'>
              " . htmlspecialchars(mb_strimwidth($s['descricao'], 0, 80, '...')) . "
              " . (!empty($s['link']) ? "<br><a href='" . htmlspecialchars($s['link']) . "' target='_blank' rel='noopener' style='font-size:11px;color:var(--brand);display:inline-flex;align-items:center;gap:4px;'><span>&#128279;</span> Ver referencia</a>" : "") . "
            </td>
            <td style='font-size:13px;color:var(--secondary);'>" . date('d/m/Y', strtotime($s['created_at'])) . "</td>
            <td><span class='badge {$badgeClass}'>{$label}</span></td>
        </tr>";
    }
    $html .= '</tbody></table></div>';
    return $html;
}
?>

<script>
function mudarTab(nome, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + nome).style.display = '';
  btn.classList.add('active');
}

async function analisar(id, acao) {
  const msg = acao === 'analisada' ? 'Marcar sugestão como analisada?' : 'Rejeitar esta sugestão?';
  if (!confirm(msg)) return;

  try {
    const res  = await fetch(`/api/sugestoes/index.php?id=${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao }),
    });
    const data = await res.json();
    if (data.success) {
      const t = document.createElement('div');
      t.className = 'alert alert-success animate-slide-up';
      t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;max-width:340px;box-shadow:var(--shadow-xl);';
      t.innerHTML = `<i data-lucide="check-circle" width="16" height="16"></i> ${data.message}`;
      document.body.appendChild(t);
      if(window.lucide) lucide.createIcons();
      setTimeout(() => { t.remove(); location.reload(); }, 1200);
    }
  } catch(e) { alert('Erro de conexão.'); }
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
