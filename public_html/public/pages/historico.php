<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Repositories\HistoricoRepository;
use App\Config\App;

AuthMiddleware::requireSecretario();

$pageTitle  = 'Histórico';
$activePage = 'historico';

$repo    = new HistoricoRepository();
$escolas = $repo->listarEscolas();

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <!-- Cabeçalho -->
  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Histórico de Solicitações</h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;">
        Registros de solicitações concluídas, canceladas e rejeitadas.
      </p>
    </div>
    <button class="btn btn-primary" id="btnExportar" onclick="exportarExcel()">
      <i data-lucide="file-spreadsheet" width="16" height="16"></i>
      Exportar Excel
    </button>
  </div>

  <!-- Filtros -->
  <div class="card-flat mb-5" style="border-radius:var(--radius-xl);padding:var(--space-5);">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--space-3);align-items:end;">

      <div>
        <label class="form-label" style="font-size:12px;">Buscar</label>
        <div style="position:relative;">
          <i data-lucide="search" width="14" height="14"
             style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--secondary);pointer-events:none;"></i>
          <input type="text" id="fBusca" placeholder="Escola ou ID..."
                 class="form-control" style="padding-left:36px;font-size:13px;"
                 oninput="agendarBusca()">
        </div>
      </div>

      <div>
        <label class="form-label" style="font-size:12px;">Escola</label>
        <select id="fEscola" class="form-control" style="font-size:13px;" onchange="buscar()">
          <option value="">Todas</option>
          <?php foreach ($escolas as $e): ?>
          <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="form-label" style="font-size:12px;">Status</label>
        <select id="fStatus" class="form-control" style="font-size:13px;" onchange="buscar()">
          <option value="">Todos</option>
          <option value="concluido">Concluído</option>
          <option value="cancelado">Cancelado</option>
          <option value="rejeitado">Rejeitado</option>
        </select>
      </div>

      <div>
        <label class="form-label" style="font-size:12px;">Categoria</label>
        <select id="fCategoria" class="form-control" style="font-size:13px;" onchange="buscar()">
          <option value="">Todas</option>
          <option value="produto">Produto</option>
          <option value="servico">Serviço</option>
        </select>
      </div>

      <div>
        <label class="form-label" style="font-size:12px;">De</label>
        <input type="date" id="fDataInicio" class="form-control" style="font-size:13px;" onchange="buscar()">
      </div>

      <div>
        <label class="form-label" style="font-size:12px;">Até</label>
        <input type="date" id="fDataFim" class="form-control" style="font-size:13px;" onchange="buscar()">
      </div>

      <div style="display:flex;align-items:flex-end;">
        <button class="btn btn-outline btn-sm w-100" onclick="limparFiltros()">
          <i data-lucide="x" width="14" height="14"></i> Limpar
        </button>
      </div>

    </div>
  </div>

  <!-- Cards de totais -->
  <div class="stats-grid mb-5" id="cardsTotais">
    <div class="stat-card">
      <div class="stat-icon stat-icon-dark"><i data-lucide="list" width="20" height="20"></i></div>
      <div><div class="stat-value" id="totTotal">—</div><div class="stat-label">Total</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-green"><i data-lucide="check-circle" width="20" height="20"></i></div>
      <div><div class="stat-value" id="totConcluidas">—</div><div class="stat-label">Concluídas</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--neutral-100);color:var(--secondary);">
        <i data-lucide="x-circle" width="20" height="20"></i>
      </div>
      <div><div class="stat-value" id="totCanceladas">—</div><div class="stat-label">Canceladas</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-brand"><i data-lucide="ban" width="20" height="20"></i></div>
      <div><div class="stat-value" id="totRejeitadas">—</div><div class="stat-label">Rejeitadas</div></div>
    </div>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:4px;background:var(--neutral-100);border-radius:var(--radius-lg);padding:4px;margin-bottom:var(--space-5);width:fit-content;">
    <button class="tab-btn active" id="tabBtnSolicita" onclick="mudarTab('solicitacoes',this)">Solicitações</button>
    <button class="tab-btn" id="tabBtnQr" onclick="mudarTab('qrlog',this)">Log de QR Code</button>
  </div>

  <!-- Tabela de solicitações -->
  <div id="tabSolicitacoes">
    <div id="loadingIndicator" style="text-align:center;padding:var(--space-8);display:none;">
      <span class="spinner spinner-brand" style="width:32px;height:32px;"></span>
    </div>
    <div class="table-wrapper animate-slide-up" id="tabelaWrapper">
      <table class="table" id="tabelaHistorico">
        <thead>
          <tr>
            <th style="width:80px;">#</th>
            <th>Escola</th>
            <th>Diretor(a)</th>
            <th style="width:90px;">Categoria</th>
            <th style="width:100px;">Status</th>
            <th>Descrição / Itens</th>
            <th style="width:110px;">Criada em</th>
            <th style="width:110px;">Encerrada em</th>
            <th style="width:60px;"></th>
          </tr>
        </thead>
        <tbody id="corpoHistorico">
          <tr>
            <td colspan="9" style="text-align:center;padding:var(--space-8);color:var(--secondary);">
              <span class="spinner spinner-brand" style="width:24px;height:24px;display:inline-block;"></span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div id="rodapeContagem" style="font-size:12px;color:var(--secondary);text-align:right;padding:var(--space-3) 0;"></div>
  </div>

  <!-- Log de QR Code -->
  <div id="tabQrlog" style="display:none;">
    <div class="table-wrapper animate-slide-up">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Solicitação</th>
            <th>Escola</th>
            <th>Diretor(a)</th>
            <th>Resultado</th>
            <th>Observação</th>
            <th>Data/Hora</th>
          </tr>
        </thead>
        <tbody id="corpoQrLog">
          <tr><td colspan="7" style="text-align:center;padding:var(--space-8);color:var(--secondary);">
            Selecione filtros e clique em buscar para ver o log.
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<style>
.tab-btn {
  background:none;border:none;padding:8px 18px;
  font-family:var(--font-primary);font-size:13px;font-weight:var(--fw-semibold);
  color:var(--secondary);border-radius:var(--radius-md);cursor:pointer;transition:var(--transition);
}
.tab-btn.active { background:var(--white);color:var(--primary);box-shadow:var(--shadow-sm); }
</style>

<script>
const STATUS_LABELS = <?= json_encode(App::STATUS_LABELS) ?>;
const STATUS_COLORS = <?= json_encode(App::STATUS_COLORS) ?>;
let debounceTimer = null;
let tabAtual = 'solicitacoes';

// ---- Inicializa ----
document.addEventListener('DOMContentLoaded', () => buscar());

function agendarBusca() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(buscar, 400);
}

function getFiltros() {
  return {
    busca:       document.getElementById('fBusca').value.trim(),
    id_escola:   document.getElementById('fEscola').value,
    status:      document.getElementById('fStatus').value,
    categoria:   document.getElementById('fCategoria').value,
    data_inicio: document.getElementById('fDataInicio').value,
    data_fim:    document.getElementById('fDataFim').value,
  };
}

function limparFiltros() {
  ['fBusca','fDataInicio','fDataFim'].forEach(id => document.getElementById(id).value = '');
  ['fEscola','fStatus','fCategoria'].forEach(id => document.getElementById(id).value = '');
  buscar();
}

async function buscar() {
  document.getElementById('loadingIndicator').style.display = '';
  document.getElementById('tabelaWrapper').style.opacity = '0.4';

  const params = new URLSearchParams(
    Object.entries(getFiltros()).filter(([,v]) => v)
  );

  try {
    const res  = await fetch('/api/historico/index.php?' + params);
    const data = await res.json();
    if (!data.success) return;

    // Totais
    const t = data.data.totais || {};
    document.getElementById('totTotal').textContent      = t.total      || 0;
    document.getElementById('totConcluidas').textContent = t.concluidas  || 0;
    document.getElementById('totCanceladas').textContent = t.canceladas  || 0;
    document.getElementById('totRejeitadas').textContent = t.rejeitadas  || 0;

    renderTabela(data.data.solicitacoes || []);

  } catch(e) {
    document.getElementById('corpoHistorico').innerHTML =
      '<tr><td colspan="9" style="text-align:center;color:var(--brand);padding:var(--space-6);">Erro ao carregar dados.</td></tr>';
  } finally {
    document.getElementById('loadingIndicator').style.display = 'none';
    document.getElementById('tabelaWrapper').style.opacity = '1';
  }
}

function renderTabela(rows) {
  const corpo = document.getElementById('corpoHistorico');

  if (!rows.length) {
    corpo.innerHTML = `<tr><td colspan="9">
      <div class="empty-state" style="padding:var(--space-10);">
        <div class="empty-state-icon"><i data-lucide="inbox" width="28" height="28"></i></div>
        <p class="empty-state-text">Nenhum registro encontrado com os filtros selecionados.</p>
      </div>
    </td></tr>`;
    if (window.lucide) lucide.createIcons();
    document.getElementById('rodapeContagem').textContent = '';
    return;
  }

  corpo.innerHTML = rows.map((r, i) => {
    const idPad      = '#' + String(r.id).padStart(4,'0');
    const statusLabel= STATUS_LABELS[r.status] || r.status;
    const statusColor= STATUS_COLORS[r.status] || '';
    const catLabel   = r.categoria === 'produto' ? 'Produto' : 'Serviço';
    const catBadge   = 'badge-' + r.categoria;
    const dtCriada   = fmtData(r.created_at);
    const dtEnc      = fmtData(r.updated_at);

    const desc = r.descricao_servico
      ? escHtml(r.descricao_servico.substring(0, 60)) + (r.descricao_servico.length > 60 ? '…' : '')
      : '<span style="color:var(--secondary);font-size:12px;">(Produto — ver detalhe)</span>';

    return `<tr>
      <td><span style="font-family:var(--font-primary);font-weight:600;">${idPad}</span></td>
      <td style="font-weight:500;">${escHtml(r.escola_nome)}</td>
      <td style="font-size:13px;color:var(--secondary);">${escHtml(r.diretor_nome)}</td>
      <td><span class="badge ${catBadge}">${catLabel}</span></td>
      <td><span class="badge ${statusColor}">${statusLabel}</span></td>
      <td style="font-size:13px;">${desc}</td>
      <td style="font-size:12px;color:var(--secondary);">${dtCriada}</td>
      <td style="font-size:12px;color:var(--secondary);">${dtEnc}</td>
      <td>
        <a href="/pages/solicitacao-detalhe.php?id=${r.id}"
           class="btn btn-ghost btn-icon-sm" title="Ver detalhe" target="_blank">
          <i data-lucide="external-link" width="13" height="13"></i>
        </a>
      </td>
    </tr>`;
  }).join('');

  document.getElementById('rodapeContagem').textContent =
    `${rows.length} registro${rows.length !== 1 ? 's' : ''} encontrado${rows.length !== 1 ? 's' : ''}.`;

  if (window.lucide) lucide.createIcons();
}

// ---- Tab QR Log ----
function mudarTab(tab, btn) {
  tabAtual = tab;
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tabSolicitacoes').style.display = tab === 'solicitacoes' ? '' : 'none';
  document.getElementById('tabQrlog').style.display        = tab === 'qrlog'        ? '' : 'none';

  if (tab === 'qrlog') carregarLogQr();
}

async function carregarLogQr() {
  const params = new URLSearchParams(
    Object.entries(getFiltros()).filter(([,v]) => v)
  );
  params.append('aba', 'qr');

  const corpo = document.getElementById('corpoQrLog');
  corpo.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:var(--space-8);"><span class="spinner spinner-brand" style="width:24px;height:24px;display:inline-block;"></span></td></tr>';

  try {
    const res  = await fetch('/api/historico/index.php?' + params);
    const data = await res.json();
    if (!data.success) return;

    // O endpoint principal não retorna log_qr — fazemos segundo request dedicado
    // (por ora, mostramos mensagem orientativa)
    corpo.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:var(--space-6);color:var(--secondary);font-size:13px;">Use o filtro de datas para carregar o log de QR Code e exporte em Excel para visualização completa.</td></tr>';
  } catch(e) {
    corpo.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--brand);">Erro ao carregar.</td></tr>';
  }
}

// ---- Exportar Excel ----
async function exportarExcel() {
  const btn = document.getElementById('btnExportar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:15px;height:15px;border-width:2px;border-top-color:var(--white);"></span> Gerando...';

  const params = new URLSearchParams(
    Object.entries(getFiltros()).filter(([,v]) => v)
  );
  params.append('acao', 'exportar');

  try {
    const res = await fetch('/api/historico/index.php?' + params);

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      toast(err.message || 'Erro ao gerar o arquivo.', 'danger');
      return;
    }

    // Força download
    const blob     = await res.blob();
    const url      = URL.createObjectURL(blob);
    const a        = document.createElement('a');
    a.href         = url;
    a.download     = `historico-solicitacoes-${new Date().toISOString().slice(0,10)}.xlsx`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);

    toast('Arquivo Excel gerado com sucesso!', 'success');
  } catch(e) {
    toast('Erro ao gerar o arquivo. Tente novamente.', 'danger');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="file-spreadsheet" width="16" height="16"></i> Exportar Excel';
    if (window.lucide) lucide.createIcons();
  }
}

// ---- Utilitários ----
function fmtData(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour:'2-digit', minute:'2-digit' });
}
function escHtml(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(s || ''));
  return d.innerHTML;
}
function toast(msg, tipo) {
  const t = document.createElement('div');
  t.className = `alert alert-${tipo} animate-slide-up`;
  t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;max-width:360px;box-shadow:var(--shadow-xl);';
  t.innerHTML = `<i data-lucide="${tipo==='success'?'check-circle':'alert-circle'}" width="16" height="16"></i> ${msg}`;
  document.body.appendChild(t);
  if (window.lucide) lucide.createIcons();
  setTimeout(() => t.remove(), 4000);
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
