<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Services\SolicitacaoService;
use App\Config\App;

AuthMiddleware::requireDiretor();

$pageTitle    = 'Minhas Solicitações';
$activePage   = 'solicitacoes';
$service      = new SolicitacaoService();
$solicitacoes = $service->listarParaDiretor(Session::getIdEscola());
$labels       = App::STATUS_LABELS;
$colors       = App::STATUS_COLORS;

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <!-- Cabeçalho -->
  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Minhas Solicitações</h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;">
        <?= count($solicitacoes) ?> solicitaç<?= count($solicitacoes) !== 1 ? 'ões' : 'ão' ?> encontrada<?= count($solicitacoes) !== 1 ? 's' : '' ?>.
      </p>
    </div>
    <a href="/pages/nova-solicitacao.php" class="btn btn-primary">
      <i data-lucide="plus" width="16" height="16"></i> Nova solicitação
    </a>
  </div>

  <!-- Filtros -->
  <div class="d-flex gap-3 mb-4 flex-wrap">
    <div style="position:relative;flex:1;min-width:180px;">
      <i data-lucide="search" width="16" height="16"
         style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--secondary);pointer-events:none;"></i>
      <input type="text" placeholder="Buscar..." class="form-control"
             style="padding-left:40px;" oninput="filtrar()">
    </div>
    <select class="form-control" id="filtroStatus" style="width:auto;" onchange="filtrar()">
      <option value="">Todos os status</option>
      <?php foreach ($labels as $k => $v): ?>
      <option value="<?= $k ?>"><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-control" id="filtroCategoria" style="width:auto;" onchange="filtrar()">
      <option value="">Todas as categorias</option>
      <option value="produto">Produto</option>
      <option value="servico">Serviço</option>
    </select>
  </div>

  <!-- Lista -->
  <?php if (empty($solicitacoes)): ?>
  <div class="card-flat" style="border-radius:var(--radius-xl);">
    <div class="empty-state" style="padding:var(--space-12);">
      <div class="empty-state-icon"><i data-lucide="inbox" width="32" height="32"></i></div>
      <p class="empty-state-title">Nenhuma solicitação ainda</p>
      <p class="empty-state-text">Crie sua primeira solicitação para a Secretaria de Ensino.</p>
      <a href="/pages/nova-solicitacao.php" class="btn btn-primary mt-4">
        <i data-lucide="plus" width="16" height="16"></i> Nova solicitação
      </a>
    </div>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:var(--space-3);" id="listaSols">
    <?php foreach ($solicitacoes as $s):
      $idPad = '#' . str_pad((string)$s['id'], 4, '0', STR_PAD_LEFT);
      $data  = date('d/m/Y H:i', strtotime($s['created_at']));
      $statusLabel = $labels[$s['status']] ?? $s['status'];
      $statusColor = $colors[$s['status']] ?? '';
      $podeCancelar = $s['status'] === 'pendente';
    ?>
    <div class="card sol-item animate-slide-up"
         data-status="<?= $s['status'] ?>"
         data-categoria="<?= $s['categoria'] ?>"
         data-busca="<?= strtolower($idPad . ' ' . $s['status']) ?>"
         style="border-radius:var(--radius-xl);padding:var(--space-4) var(--space-5);cursor:pointer;"
         onclick="window.location='/pages/solicitacao-detalhe.php?id=<?= $s['id'] ?>'">

      <div style="display:flex;align-items:center;gap:var(--space-4);flex-wrap:wrap;">

        <!-- Ícone de status -->
        <div style="width:40px;height:40px;border-radius:var(--radius-md);flex-shrink:0;
                    display:flex;align-items:center;justify-content:center;
                    background:<?= $s['status'] === 'concluido' ? '#dcfce7' : ($s['status'] === 'cancelado' || $s['status'] === 'rejeitado' ? '#fee2e2' : ($s['status'] === 'despachado' ? '#d1fae5' : 'var(--neutral-100)')) ?>;">
          <?php
          $icons = [
            'pendente' => 'clock', 'em_analise' => 'search', 'em_processamento' => 'settings',
            'despachado' => 'truck', 'retornado' => 'undo-2', 'item_em_falta' => 'package-x',
            'concluido' => 'check-circle', 'cancelado' => 'x-circle', 'rejeitado' => 'ban',
          ];
          $icon = $icons[$s['status']] ?? 'circle';
          ?>
          <i data-lucide="<?= $icon ?>" width="20" height="20"
             style="color:<?= in_array($s['status'], ['concluido']) ? '#16a34a' : (in_array($s['status'], ['cancelado','rejeitado']) ? 'var(--brand)' : ($s['status'] === 'despachado' ? '#065f46' : 'var(--secondary)')) ?>"></i>
        </div>

        <!-- Info -->
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:3px;flex-wrap:wrap;">
            <span style="font-family:var(--font-primary);font-weight:var(--fw-bold);"><?= $idPad ?></span>
            <span class="badge badge-<?= $s['categoria'] ?>"><?= $s['categoria'] === 'produto' ? 'Produto' : 'Serviço' ?></span>
            <span class="badge <?= $statusColor ?>"><?= $statusLabel ?></span>
          </div>
          <?php if ($s['descricao_servico']): ?>
          <div style="font-size:12px;color:var(--secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px;">
            "<?= htmlspecialchars(mb_strimwidth($s['descricao_servico'], 0, 70, '...')) ?>"
          </div>
          <?php endif; ?>
          <div style="font-size:12px;color:var(--neutral-300);margin-top:3px;"><?= $data ?></div>
        </div>

        <!-- Ações -->
        <div style="display:flex;align-items:center;gap:var(--space-2);flex-shrink:0;" onclick="event.stopPropagation();">
          <?php if ($s['status'] === App::STATUS_DESPACHADO): ?>
          <span class="badge badge-dispatched animate-pulse-brand" style="font-size:11px;">
            <i data-lucide="qr-code" width="11" height="11"></i> Aguardando QR
          </span>
          <?php endif; ?>
          <?php if ($podeCancelar): ?>
          <button class="btn btn-ghost btn-sm" style="color:var(--brand);font-size:12px;"
                  onclick="cancelar(<?= $s['id'] ?>)">
            Cancelar
          </button>
          <?php endif; ?>
          <i data-lucide="chevron-right" width="16" height="16" style="color:var(--neutral-300);"></i>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<style>
.sol-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-lg); }
.sol-item { transition:var(--transition); }
</style>

<script>
function filtrar() {
  const busca = document.querySelector('input[placeholder="Buscar..."]').value.toLowerCase();
  const status = document.getElementById('filtroStatus').value;
  const cat    = document.getElementById('filtroCategoria').value;
  document.querySelectorAll('.sol-item').forEach(el => {
    const ok = (!busca  || el.dataset.busca.includes(busca))
            && (!status || el.dataset.status === status)
            && (!cat    || el.dataset.categoria === cat);
    el.style.display = ok ? '' : 'none';
  });
}

async function cancelar(id) {
  if (!confirm('Confirma o cancelamento desta solicitação?')) return;
  try {
    const res  = await fetch(`/api/solicitacoes/index.php?id=${id}&acao=cancelar`, { method: 'POST' });
    const data = await res.json();
    if (data.success) {
      const t = document.createElement('div');
      t.className = 'alert alert-success animate-slide-up';
      t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;max-width:340px;box-shadow:var(--shadow-xl);';
      t.innerHTML = `<i data-lucide="check-circle" width="16" height="16"></i> ${data.message}`;
      document.body.appendChild(t);
      if (window.lucide) lucide.createIcons();
      setTimeout(() => location.reload(), 1200);
    } else {
      alert(data.message);
    }
  } catch(e) { alert('Erro de conexão.'); }
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
