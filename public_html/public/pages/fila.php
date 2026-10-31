<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Services\SolicitacaoService;
use App\Config\App;

AuthMiddleware::requireAdmin();

$pageTitle  = 'Fila de Pedidos';
$activePage = 'fila';
$categoria  = Session::getCategoria() ?? 'produto';
$catLabel   = $categoria === 'produto' ? 'Produtos' : 'Serviços';

$service    = new SolicitacaoService();
$solicitacoes = $service->listarFila($categoria);

$statusLabels = App::STATUS_LABELS;
$statusColors = App::STATUS_COLORS;

// Agrupa por status para contagem
$contagens = [];
foreach ($solicitacoes as $s) {
    $contagens[$s['status']] = ($contagens[$s['status']] ?? 0) + 1;
}

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <!-- Cabeçalho -->
  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Fila de <?= $catLabel ?></h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;">
        <?= count($solicitacoes) ?> solicitaç<?= count($solicitacoes) !== 1 ? 'ões ativas' : 'ão ativa' ?> na fila.
      </p>
    </div>
    <!-- Filtro de status -->
    <div class="d-flex gap-3 flex-wrap">
      <select class="form-control" id="filtroStatus" style="width:auto;" onchange="filtrarFila()">
        <option value="">Todos os status</option>
        <?php foreach (['pendente','em_analise','em_processamento','despachado','retornado','item_em_falta'] as $s): ?>
        <option value="<?= $s ?>"><?= $statusLabels[$s] ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-control" id="filtroEscola" style="width:auto;" onchange="filtrarFila()">
        <option value="">Todas as escolas</option>
        <?php
        $escolas = array_unique(array_column($solicitacoes, 'escola_nome'));
        sort($escolas);
        foreach ($escolas as $esc): ?>
        <option value="<?= htmlspecialchars($esc) ?>"><?= htmlspecialchars($esc) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="stats-grid mb-6">
    <?php
    $statsConfig = [
        'pendente'         => ['Aguardando',     'inbox',    'stat-icon-brand'],
        'em_analise'       => ['Em análise',      'search',   'stat-icon-dark'],
        'em_processamento' => ['Processando',     'settings', 'stat-icon-dark'],
        'despachado'       => ['Despachados',     'truck',    'stat-icon-green'],
        'retornado'        => ['Retornados',      'undo-2',   'stat-icon-brand'],
        'item_em_falta'    => ['Item em falta',   'package-x','stat-icon-amber'],
    ];
    foreach ($statsConfig as $st => $cfg): ?>
    <div class="stat-card" style="cursor:pointer;" onclick="document.getElementById('filtroStatus').value='<?= $st ?>'; filtrarFila();">
      <div class="stat-icon <?= $cfg[2] ?>">
        <i data-lucide="<?= $cfg[1] ?>" width="20" height="20"></i>
      </div>
      <div>
        <div class="stat-value" style="font-size:22px;"><?= $contagens[$st] ?? 0 ?></div>
        <div class="stat-label"><?= $cfg[0] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Lista de solicitações -->
  <div id="listaFila" style="display:flex;flex-direction:column;gap:var(--space-3);">
    <?php if (empty($solicitacoes)): ?>
    <div class="card-flat" style="border-radius:var(--radius-xl);">
      <div class="empty-state" style="padding:var(--space-12);">
        <div class="empty-state-icon"><i data-lucide="inbox" width="32" height="32"></i></div>
        <p class="empty-state-title">Fila vazia!</p>
        <p class="empty-state-text">Nenhuma solicitação ativa no momento.</p>
      </div>
    </div>
    <?php else: ?>
    <?php foreach ($solicitacoes as $s):
      $statusLabel = $statusLabels[$s['status']] ?? $s['status'];
      $statusColor = $statusColors[$s['status']] ?? '';
      $idPad = '#' . str_pad((string)$s['id'], 4, '0', STR_PAD_LEFT);
      $data = date('d/m/Y H:i', strtotime($s['created_at']));
    ?>
    <div class="card fila-item animate-slide-up"
         data-status="<?= $s['status'] ?>"
         data-escola="<?= htmlspecialchars($s['escola_nome']) ?>"
         style="border-radius:var(--radius-xl);cursor:pointer;padding: var(--space-4) var(--space-5);"
         onclick="abrirDetalhe(<?= $s['id'] ?>)">

      <div style="display:flex;align-items:center;gap:var(--space-4);flex-wrap:wrap;">

        <!-- ID e Escola -->
        <div style="flex:1;min-width:180px;">
          <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:4px;">
            <span style="font-family:var(--font-primary);font-weight:var(--fw-bold);font-size:15px;"><?= $idPad ?></span>
            <span class="badge badge-<?= $s['categoria'] ?>"><?= $s['categoria'] === 'produto' ? 'Produto' : 'Serviço' ?></span>
          </div>
          <div style="font-size:13px;color:var(--secondary);">
            <i data-lucide="school" width="12" height="12" style="display:inline;vertical-align:middle;margin-right:4px;"></i>
            <?= htmlspecialchars($s['escola_nome']) ?>
          </div>
          <?php if ($s['descricao_servico']): ?>
          <div style="font-size:12px;color:var(--secondary);margin-top:3px;font-style:italic;">
            "<?= htmlspecialchars(mb_strimwidth($s['descricao_servico'], 0, 60, '...')) ?>"
          </div>
          <?php endif; ?>
        </div>

        <!-- Diretor e data -->
        <div style="font-size:12px;color:var(--secondary);text-align:right;min-width:120px;">
          <div><?= htmlspecialchars($s['diretor_nome']) ?></div>
          <div style="margin-top:3px;"><?= $data ?></div>
        </div>

        <!-- Status e ação -->
        <div style="display:flex;align-items:center;gap:var(--space-3);">
          <span class="badge <?= $statusColor ?>"><?= $statusLabel ?></span>

          <?php if ($s['status'] === App::STATUS_DESPACHADO): ?>
          <button class="btn btn-outline btn-sm animate-pulse-brand"
                  onclick="event.stopPropagation(); verQrCode(<?= $s['id'] ?>, '<?= htmlspecialchars($s['qr_token']) ?>')"
                  title="Ver QR Code">
            <i data-lucide="qr-code" width="14" height="14"></i>
            QR Code
          </button>
          <?php endif; ?>

          <i data-lucide="chevron-right" width="16" height="16" style="color:var(--neutral-300);"></i>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ====== MODAL DETALHE / AÇÕES ====== -->
<div class="modal-overlay" id="modalDetalhe" style="display:none;" onclick="fecharModalFora(event,'modalDetalhe')">
  <div class="modal" style="max-width:580px;">
    <div class="modal-header">
      <h3 class="modal-title" id="detalheTitle">Solicitação</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal('modalDetalhe')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body" id="detalheBody">
      <div style="text-align:center;padding:var(--space-8);">
        <span class="spinner spinner-brand" style="width:32px;height:32px;"></span>
      </div>
    </div>
    <div class="modal-footer" id="detalheFooter"></div>
  </div>
</div>

<!-- ====== MODAL QR CODE (despachado) ====== -->
<div class="modal-overlay" id="modalQr" style="display:none;" onclick="fecharModalFora(event,'modalQr')">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header">
      <h3 class="modal-title">QR Code da Entrega</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal('modalQr')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body" style="text-align:center;">
      <p class="text-body mb-4" style="color:var(--secondary);font-size:13px;">
        Apresente ou salve este QR Code. Ele será escaneado pelo diretor no momento da entrega.
      </p>
      <div class="qr-container">
        <img id="qrImg" src="" alt="QR Code" class="qr-img">
        <div class="qr-label" id="qrLabel"></div>
      </div>
      <div class="mt-4">
        <a id="qrDownload" href="#" download="qrcode-solicitacao.png" class="btn btn-outline btn-sm">
          <i data-lucide="download" width="14" height="14"></i> Salvar imagem
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ====== MODAL REJEITAR ====== -->
<div class="modal-overlay" id="modalRejeitar" style="display:none;" onclick="fecharModalFora(event,'modalRejeitar')">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <h3 class="modal-title">Rejeitar Solicitação</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal('modalRejeitar')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning mb-4">
        <i data-lucide="alert-triangle" width="16" height="16"></i>
        Esta ação não pode ser desfeita. O diretor será notificado.
      </div>
      <div class="form-group">
        <label class="form-label">Motivo da rejeição <span class="required">*</span></label>
        <textarea id="motivoRejeicao" class="form-control" rows="3"
                  placeholder="Descreva o motivo da rejeição..." maxlength="300"
                  data-maxlength="300" data-counter="cntMotivo"></textarea>
        <div class="char-counter" id="cntMotivo">0/300</div>
      </div>
      <input type="hidden" id="idRejeitarSol">
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharModal('modalRejeitar')">Cancelar</button>
      <button class="btn btn-danger" id="btnConfirmarRejeitar" onclick="confirmarRejeitar()">
        <i data-lucide="x-circle" width="14" height="14"></i> Rejeitar
      </button>
    </div>
  </div>
</div>

<style>
.fila-item:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.fila-item { transition: var(--transition); }
.modal-body::-webkit-scrollbar { width: 4px; }
.modal-body::-webkit-scrollbar-track { background: transparent; }
.modal-body::-webkit-scrollbar-thumb { background: var(--neutral-300); border-radius: 99px; }
.modal-body::-webkit-scrollbar-thumb:hover { background: var(--secondary); }
.modal-body { scrollbar-width: thin; scrollbar-color: var(--neutral-300) transparent; }
</style>

<script>
// ---- Filtros ----
function filtrarFila() {
  const status = document.getElementById('filtroStatus').value;
  const escola = document.getElementById('filtroEscola').value;
  document.querySelectorAll('.fila-item').forEach(el => {
    const okStatus = !status || el.dataset.status === status;
    const okEscola = !escola || el.dataset.escola === escola;
    el.style.display = okStatus && okEscola ? '' : 'none';
  });
}

// ---- Detalhe da solicitação ----
async function abrirDetalhe(id) {
  document.getElementById('modalDetalhe').style.display = 'flex';
  document.getElementById('detalheBody').innerHTML =
    '<div style="text-align:center;padding:var(--space-8);"><span class="spinner spinner-brand" style="width:32px;height:32px;"></span></div>';
  document.getElementById('detalheFooter').innerHTML = '';

  try {
    const res  = await fetch(`/api/solicitacoes/index.php?id=${id}`);
    const data = await res.json();
    if (!data.success) { document.getElementById('detalheBody').innerHTML = '<p class="text-body" style="color:var(--brand);">Erro ao carregar.</p>'; return; }

    const s = data.data.solicitacao;
    renderDetalhe(s);
  } catch(e) {
    document.getElementById('detalheBody').innerHTML = '<p style="color:var(--brand);">Erro de conexão.</p>';
  }
}

function renderDetalhe(s) {
  const labels = <?= json_encode(App::STATUS_LABELS) ?>;
  const colors = <?= json_encode(App::STATUS_COLORS) ?>;
  const idPad  = '#' + String(s.id).padStart(4,'0');

  document.getElementById('detalheTitle').textContent = `Solicitação ${idPad}`;

  // Itens de produto
  let itensHtml = '';
  if (s.categoria === 'produto' && s.itens?.length) {
    const entregues = s.itens_entregues || [];
    const temEntregues = entregues.length > 0;
    itensHtml = `<div style="margin-top:var(--space-4);">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-2);">
        <div class="form-label">Itens solicitados</div>
        ${temEntregues ? `<span style="font-size:11px;color:#16a34a;font-weight:600;display:flex;align-items:center;gap:4px;"><span>✓</span> ${entregues.length} item(ns) ja entregue(s)</span>` : ''}
      </div>
      <div style="background:var(--neutral-100);border-radius:var(--radius-md);overflow:hidden;border:1.5px solid var(--neutral-200);">
        <table style="width:100%;font-size:14px;border-collapse:collapse;">
          <thead><tr style="background:var(--primary);color:#fff;">
            <th style="padding:11px 14px;text-align:left;font-size:11px;font-weight:700;letter-spacing:0.07em;">PRODUTO</th>
            <th style="padding:11px 14px;text-align:right;font-size:11px;font-weight:700;letter-spacing:0.07em;">QUANTIDADE</th>
            <th style="padding:11px 14px;text-align:center;font-size:11px;font-weight:700;letter-spacing:0.07em;">STATUS</th>
          </tr></thead><tbody>` +
    s.itens.map(i => {
      const jaEntregue = entregues.includes(parseInt(i.id));
      return `<tr style="border-top:1px solid var(--neutral-200);${jaEntregue ? 'opacity:0.6;' : ''}">
        <td style="padding:11px 14px;font-weight:500;${jaEntregue ? 'text-decoration:line-through;color:var(--secondary);' : ''}">${i.produto_nome}</td>
        <td style="padding:11px 14px;text-align:right;font-weight:700;color:${jaEntregue ? 'var(--secondary)' : 'var(--brand)'};font-size:15px;">${parseFloat(i.quantidade)} ${i.unidade_sigla}</td>
        <td style="padding:11px 14px;text-align:center;">
          ${jaEntregue
            ? `<span style="font-size:11px;background:#dcfce7;color:#16a34a;padding:2px 10px;border-radius:50px;font-weight:600;">Entregue</span>`
            : `<span style="font-size:11px;background:#fce7f3;color:#be123c;padding:2px 10px;border-radius:50px;font-weight:600;">Pendente</span>`
          }
        </td>
      </tr>`;
    }).join('') +
      `</tbody></table></div></div>`;
  }

  if (s.categoria === 'servico') {
    itensHtml = `<div style="margin-top:var(--space-4);padding:var(--space-4);background:var(--neutral-100);border-radius:var(--radius-md);">
      <div class="form-label mb-2">Descrição do serviço</div>
      <p style="font-size:14px;">${escHtml(s.descricao_servico)}</p>
    </div>`;
  }

  // Histórico
  let histHtml = '';
  if (s.historico?.length) {
    histHtml = `<div style="margin-top:var(--space-5);">
      <div class="form-label mb-3">Histórico de status</div>
      <div style="display:flex;flex-direction:column;gap:8px;">` +
      s.historico.map(h => `
        <div style="display:flex;gap:var(--space-3);align-items:flex-start;">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--brand);margin-top:5px;flex-shrink:0;"></div>
          <div>
            <div style="font-size:13px;font-weight:600;">${labels[h.status_novo] || h.status_novo}</div>
            <div style="font-size:11px;color:var(--secondary);">${h.usuario_nome} · ${new Date(h.created_at).toLocaleString('pt-BR')}</div>
            ${h.observacao ? `<div style="font-size:12px;color:var(--secondary);margin-top:2px;font-style:italic;">${escHtml(h.observacao)}</div>` : ''}
          </div>
        </div>`).join('') +
      `</div></div>`;
  }

  document.getElementById('detalheBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-bottom:var(--space-4);">
      <div><div class="text-xs" style="color:var(--secondary);margin-bottom:3px;">ESCOLA</div><div style="font-weight:600;font-size:14px;">${escHtml(s.escola_nome)}</div></div>
      <div><div class="text-xs" style="color:var(--secondary);margin-bottom:3px;">DIRETOR(A)</div><div style="font-size:14px;">${escHtml(s.diretor_nome)}</div></div>
      <div><div class="text-xs" style="color:var(--secondary);margin-bottom:3px;">CATEGORIA</div><span class="badge badge-${s.categoria}">${s.categoria === 'produto' ? 'Produto' : 'Serviço'}</span></div>
      <div><div class="text-xs" style="color:var(--secondary);margin-bottom:3px;">STATUS</div><span class="badge ${colors[s.status]}">${labels[s.status]}</span></div>
      <div><div class="text-xs" style="color:var(--secondary);margin-bottom:3px;">CRIADA EM</div><div style="font-size:13px;">${new Date(s.created_at).toLocaleString('pt-BR')}</div></div>
    </div>
    ${itensHtml}
    ${histHtml}
  `;

  // Botões de ação conforme status
  renderBotoesAcao(s);
  if (window.lucide) lucide.createIcons();
}

function renderBotoesAcao(s) {
  const footer = document.getElementById('detalheFooter');
  const transicoes = {
    'pendente':         ['em_analise','rejeitado'],
    'em_analise':       ['em_processamento','rejeitado'],
    'em_processamento': ['despachado'],
    'retornado':        ['pendente'],
    'item_em_falta':    ['pendente'],
  };

  const labelsAcao = {
    'em_analise':       ['search',     'Em Análise',      'btn-outline'],
    'em_processamento': ['settings',   'Em Processamento','btn-outline'],
    'despachado':       ['truck',      'Despachar',       'btn-primary'],
    'pendente':         ['undo-2',     'Recolocar Pendente','btn-outline'],
    'rejeitado':        ['x-circle',  'Rejeitar',         'btn-danger'],
  };

  const acoes = transicoes[s.status] || [];
  if (!acoes.length) { footer.innerHTML = '<span style="font-size:13px;color:var(--secondary);">Sem ações disponíveis.</span>'; return; }

  let html = `<button class="btn btn-ghost" onclick="fecharModal('modalDetalhe')">Fechar</button><div class="d-flex gap-2 flex-wrap">`;

  acoes.forEach(acao => {
    if (acao === 'rejeitado') {
      html += `<button class="btn btn-danger btn-sm" onclick="abrirRejeitar(${s.id})">
        <i data-lucide="x-circle" width="14" height="14"></i> Rejeitar
      </button>`;
    } else {
      const cfg = labelsAcao[acao] || ['arrow-right', acao, 'btn-outline'];
      html += `<button class="btn ${cfg[2]} btn-sm" onclick="mudarStatus(${s.id},'${acao}','${s.categoria}')">
        <i data-lucide="${cfg[0]}" width="14" height="14"></i> ${cfg[1]}
      </button>`;
    }
  });

  // Botão QR Code sempre visível se despachado
  if (s.status === 'despachado') {
    html += `<button class="btn btn-brand btn-sm animate-pulse-brand" onclick="fecharModal('modalDetalhe');verQrCode(${s.id},'${s.qr_token}')">
      <i data-lucide="qr-code" width="14" height="14"></i> Ver QR Code
    </button>`;
  }

  html += '</div>';
  footer.innerHTML = html;
  if (window.lucide) lucide.createIcons();
}

// ---- Mudar status ----
async function mudarStatus(id, novoStatus, categoria) {
  const btn = event.target.closest('button');
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span>'; }

  try {
    const res  = await fetch(`/api/solicitacoes/index.php?id=${id}&acao=status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: novoStatus, categoria_override: categoria }),
    });
    const data = await res.json();

    if (data.success) {
      fecharModal('modalDetalhe');

      // Se despachado → abre QR automaticamente após reload
      if (novoStatus === 'despachado') {
        sessionStorage.setItem('abrirQr', id);
      }

      toast(data.message, 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      toast(data.message, 'danger');
      if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.originalText || 'Ação'; }
    }
  } catch(e) { toast('Erro de conexão.', 'danger'); }
}

// ---- QR Code ----
async function verQrCode(id, token) {
  document.getElementById('modalQr').style.display = 'flex';
  document.getElementById('qrLabel').textContent = `Solicitação #${String(id).padStart(4,'0')}`;

  // Gera QR via Google Charts API (sem dependência externa pesada)
  const url = `<?= $_ENV['APP_URL'] ?? '' ?>/public/pages/qr-leitura.php?token=${encodeURIComponent(token)}`;
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`;

  document.getElementById('qrImg').src = qrUrl;
  document.getElementById('qrDownload').href = qrUrl;
  document.getElementById('qrDownload').download = `qr-solicitacao-${id}.png`;
}

// Verifica se há QR para abrir automaticamente (após despacho)
document.addEventListener('DOMContentLoaded', () => {
  const idQr = sessionStorage.getItem('abrirQr');
  if (idQr) {
    sessionStorage.removeItem('abrirQr');
    // Busca o token do item despachado
    const item = document.querySelector(`.fila-item[data-status="despachado"]`);
    if (item) item.click();
  }
});

// ---- Rejeitar ----
function abrirRejeitar(id) {
  document.getElementById('idRejeitarSol').value = id;
  document.getElementById('motivoRejeicao').value = '';
  fecharModal('modalDetalhe');
  document.getElementById('modalRejeitar').style.display = 'flex';
  document.getElementById('motivoRejeicao').dispatchEvent(new Event('input'));
}

async function confirmarRejeitar() {
  const id    = document.getElementById('idRejeitarSol').value;
  const motivo= document.getElementById('motivoRejeicao').value.trim();
  if (!motivo) { alert('Informe o motivo da rejeição.'); return; }

  const btn = document.getElementById('btnConfirmarRejeitar');
  btn.disabled = true;

  try {
    const res  = await fetch(`/api/solicitacoes/index.php?id=${id}&acao=status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: 'rejeitado', motivo_rejeicao: motivo }),
    });
    const data = await res.json();
    if (data.success) { fecharModal('modalRejeitar'); toast(data.message,'success'); setTimeout(()=>location.reload(),800); }
    else toast(data.message,'danger');
  } catch(e) { toast('Erro.','danger'); }
  finally { btn.disabled = false; }
}

// ---- Utilitários ----
function fecharModal(id)  { document.getElementById(id).style.display = 'none'; }
function fecharModalFora(e, id) { if (e.target.id === id) fecharModal(id); }
function escHtml(str) { const d=document.createElement('div'); d.appendChild(document.createTextNode(str||'')); return d.innerHTML; }

function toast(msg, tipo) {
  const t = document.createElement('div');
  t.className = `alert alert-${tipo} animate-slide-up`;
  t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;max-width:340px;box-shadow:var(--shadow-xl);';
  t.innerHTML = `<i data-lucide="${tipo==='success'?'check-circle':'alert-circle'}" width="16" height="16"></i> ${msg}`;
  document.body.appendChild(t);
  if(window.lucide) lucide.createIcons();
  setTimeout(()=>t.remove(),3500);
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
