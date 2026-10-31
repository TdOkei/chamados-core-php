<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireSecretario();

$pageTitle  = 'Relatórios';
$activePage = 'relatorios';

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in" id="paginaRelatorios">

  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Relatórios e Indicadores</h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;">
        Visão analítica completa do sistema de pedidos.
      </p>
    </div>
    <button class="btn btn-outline btn-sm" onclick="carregarDados(true)" id="btnAtualizar">
      <i data-lucide="refresh-cw" width="14" height="14"></i> Atualizar
    </button>
  </div>


  <!-- Filtros -->
  <div class="card mb-6" style="padding:var(--space-4);">
    <div style="display:flex;gap:var(--space-4);align-items:flex-end;flex-wrap:wrap;">
      <div style="flex:1;min-width:140px;">
        <label class="form-label">Data início</label>
        <input type="date" id="filtroDataInicio" class="form-control" 
               onchange="carregarDados(false)">
      </div>
      <div style="flex:1;min-width:140px;">
        <label class="form-label">Data fim</label>
        <input type="date" id="filtroDataFim" class="form-control"
               onchange="carregarDados(false)">
      </div>
      <div style="flex:2;min-width:180px;">
        <label class="form-label">Escola</label>
        <select id="filtroEscola" class="form-control" onchange="carregarDados(false)">
          <option value="">Todas as escolas</option>
        </select>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="limparFiltros()" title="Limpar filtros">
        <i data-lucide="x" width="14" height="14"></i> Limpar
      </button>
    </div>
  </div>

  <!-- Loading state -->
  <div id="loadingRelatorio" style="text-align:center;padding:var(--space-16);">
    <span class="spinner spinner-brand" style="width:40px;height:40px;"></span>
    <p style="color:var(--secondary);margin-top:var(--space-4);font-size:14px;">Carregando dados...</p>
  </div>

  <!-- Conteúdo (oculto até carregar) -->
  <div id="conteudoRelatorio" style="display:none;">

    <!-- ===== KPIs Principais ===== -->
    <div class="stats-grid mb-6" id="kpiGrid"></div>

    <!-- ===== Linha 1: Gráfico barras por mês + pizza status ===== -->
    <div class="grid-2 mb-8" style="gap:var(--space-6);">

      <div class="card" style="border-radius:var(--radius-xl);padding-bottom:var(--space-6);">
        <div class="card-header">
          <h3 class="card-title">Solicitações por Mês</h3>
          <span style="font-size:12px;color:var(--secondary);">Últimos 12 meses</span>
        </div>
        <canvas id="graficoMes" style="max-height:260px;"></canvas>
      </div>

      <div class="card" style="border-radius:var(--radius-xl);padding-bottom:var(--space-6);">
        <div class="card-header">
          <h3 class="card-title">Distribuição por Status</h3>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;gap:var(--space-6);">
          <canvas id="graficoPizza" style="max-height:220px;max-width:220px;"></canvas>
          <div id="legendaPizza" style="display:flex;flex-direction:column;gap:var(--space-2);font-size:12px;"></div>
        </div>
      </div>

    </div>

    <!-- ===== Linha 2: Tabela escolas + Top produtos ===== -->
    <div class="grid-2 mb-8" style="gap:var(--space-6);">

      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Solicitações por Escola</h3>
        </div>
        <div class="table-wrapper" style="box-shadow:none;">
          <table class="table" id="tabelaEscolas">
            <thead>
              <tr>
                <th>Escola</th>
                <th style="text-align:right;">Total</th>
                <th style="text-align:right;">Concluídas</th>
                <th style="text-align:right;">Em aberto</th>
                <th style="text-align:right;">%</th>
              </tr>
            </thead>
            <tbody id="corpoEscolas"></tbody>
          </table>
        </div>
      </div>

      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Top Produtos Solicitados</h3>
        </div>
        <div id="listaTopProdutos" style="display:flex;flex-direction:column;gap:var(--space-2);padding:0 var(--space-4) var(--space-4);"></div>
      </div>

    </div>

    <!-- ===== Linha 3: Tempo médio + QR Code stats ===== -->
    <div class="grid-2 mb-8" style="gap:var(--space-6);">

      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Tempo Médio de Conclusão</h3>
          <span style="font-size:12px;color:var(--secondary);">Por escola (dias)</span>
        </div>
        <div id="tempoMedio" style="display:flex;flex-direction:column;gap:var(--space-3);padding:0 var(--space-4) var(--space-4);"></div>
      </div>

      <div class="card" style="border-radius:var(--radius-xl);">
        <div class="card-header">
          <h3 class="card-title">Estatísticas de QR Code</h3>
        </div>
        <div id="statsQr" style="display:flex;flex-direction:column;gap:var(--space-3);padding:0 var(--space-4) var(--space-2);"></div>
        <div style="margin-top:var(--space-5);">
          <div class="card-title mb-3" style="font-size:14px;">Categoria das Solicitações</div>
          <canvas id="graficoCategoria" style="max-height:120px;padding-bottom:var(--space-2);"></canvas>
        </div>
      </div>

    </div>

    <!-- ===== Linha 4: Itens Faltantes Recorrentes ===== -->
    <div class="card mb-8" style="border-radius:var(--radius-xl);">
      <div class="card-header">
        <div>
          <h3 class="card-title">Itens Faltantes Recorrentes</h3>
          <p style="font-size:12px;color:var(--secondary);margin-top:2px;">Produtos com mais ocorrencias de item em falta nas entregas</p>
        </div>
        <span class="badge badge-missing" id="qtdFaltantes"></span>
      </div>
      <div id="listaFaltantes">
        <div class="empty-state" style="padding:var(--space-8);">
          <i data-lucide="package-x" width="32" height="32" style="color:var(--neutral-300);margin:0 auto;"></i>
          <p class="empty-state-text">Nenhum item faltante registrado.</p>
        </div>
      </div>
    </div>

    <!-- ===== Linha 5: Usuários bloqueados ===== -->
    <div id="secaoBloqueados" class="card" style="border-radius:var(--radius-xl);display:none;">
      <div class="card-header">
        <h3 class="card-title" style="color:var(--brand);">
          <i data-lucide="lock" width="16" height="16" style="display:inline;vertical-align:middle;margin-right:6px;"></i>
          Contas Bloqueadas
        </h3>
        <span class="badge badge-rejected" id="qtdBloqueados"></span>
      </div>
      <div class="table-wrapper" style="box-shadow:none;">
        <table class="table">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Usuário</th>
              <th>Perfil</th>
              <th>Escola</th>
              <th>Tentativas</th>
              <th>Ação</th>
            </tr>
          </thead>
          <tbody id="corpoBloqueados"></tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let chartMes      = null;
let chartPizza    = null;
let chartCategoria= null;

const STATUS_LABELS = <?= json_encode(\App\Config\App::STATUS_LABELS) ?>;
const STATUS_COLORS_MAP = {
  'pendente':         '#FEF9C3',
  'em_analise':       '#DBEAFE',
  'em_processamento': '#EDE9FE',
  'despachado':       '#D1FAE5',
  'retornado':        '#FEE2E2',
  'item_em_falta':    '#FFEDD5',
  'concluido':        '#DCFCE7',
  'cancelado':        '#F4F4F5',
  'rejeitado':        '#FEE2E2',
};
const STATUS_BORDER_MAP = {
  'pendente':         '#EAB308',
  'em_analise':       '#3B82F6',
  'em_processamento': '#8B5CF6',
  'despachado':       '#10B981',
  'retornado':        '#EF4444',
  'item_em_falta':    '#F97316',
  'concluido':        '#16A34A',
  'cancelado':        '#71717A',
  'rejeitado':        '#DC2626',
};

document.addEventListener('DOMContentLoaded', carregarDados);

async function carregarDados(forcarAtualizacao = false) {
  const dataInicio = document.getElementById('filtroDataInicio')?.value || '';
  const dataFim    = document.getElementById('filtroDataFim')?.value || '';
  const escola     = document.getElementById('filtroEscola')?.value || '';

  const params = new URLSearchParams();
  if (forcarAtualizacao) params.append('refresh', '1');
  if (dataInicio) params.append('data_inicio', dataInicio);
  if (dataFim)    params.append('data_fim', dataFim);
  if (escola)     params.append('id_escola', escola);

  const queryStr = params.toString() ? '?' + params.toString() : '';

  document.getElementById('loadingRelatorio').style.display  = '';
  document.getElementById('conteudoRelatorio').style.display = 'none';
  document.getElementById('btnAtualizar').disabled = true;

  try {
    const url = '/api/relatorios/index.php' + queryStr;
    const res  = await fetch(url);
    const data = await res.json();
    if (!data.success) return;

    const d = data.data;

    renderKpis(d.totais_gerais);
    renderGraficoMes(d.por_mes || []);
    renderGraficoPizza(d.por_status || []);
    renderTabelaEscolas(d.por_escola || []);
    renderTopProdutos(d.top_produtos || []);
    renderTempoMedio(d.tempo_medio || []);
    renderStatsQr(d.totais_qr || []);
    renderGraficoCategoria(d.por_categoria || []);
    renderBloqueados(d.usuarios_bloqueados || []);
    renderFaltantes(d.itens_faltantes_recorrentes || []);

    document.getElementById('loadingRelatorio').style.display  = 'none';
    document.getElementById('conteudoRelatorio').style.display = '';

  } catch(e) {
    document.getElementById('loadingRelatorio').innerHTML =
      '<p style="color:var(--brand);">Erro ao carregar dados. Tente novamente.</p>';
  } finally {
    document.getElementById('btnAtualizar').disabled = false;
  }
}

// ---- Itens Faltantes Recorrentes ----
function renderFaltantes(dados) {
  const el = document.getElementById('listaFaltantes');
  const badge = document.getElementById('qtdFaltantes');
  if (!el) return;

  if (!dados.length) {
    badge.style.display = 'none';
    el.innerHTML = `
      <div class="empty-state" style="padding:var(--space-8);">
        <i data-lucide="package-check" width="32" height="32" style="color:var(--neutral-300);margin:0 auto;"></i>
        <p class="empty-state-text">Nenhum item faltante registrado no periodo.</p>
      </div>`;
    if (window.lucide) lucide.createIcons();
    return;
  }

  badge.style.display = '';
  badge.textContent   = dados.length + ' produto' + (dados.length > 1 ? 's' : '');

  const catColors = {
    papelaria: 'badge-analysis',
    higiene:   'badge-processing',
    limpeza:   'badge-dispatched',
    outros:    'badge-pending',
  };
  const catLabels = {
    papelaria: 'Papelaria',
    higiene:   'Higiene',
    limpeza:   'Limpeza',
    outros:    'Outros',
  };

  const max = dados[0]?.total_faltantes || 1;

  el.innerHTML = `
    <div class="table-wrapper" style="box-shadow:none;">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Produto</th>
            <th>Categoria</th>
            <th>Ocorrencias</th>
            <th>Solicitacoes afetadas</th>
            <th>Frequencia</th>
          </tr>
        </thead>
        <tbody>
          ${dados.map((item, i) => {
            const pct = Math.round((item.total_faltantes / max) * 100);
            const cat = item.produto_categoria || 'outros';
            return `
              <tr>
                <td style="font-weight:var(--fw-semibold);color:var(--secondary);">${i+1}</td>
                <td style="font-weight:var(--fw-semibold);">${item.produto_nome}</td>
                <td><span class="badge ${catColors[cat] || 'badge-pending'}">${catLabels[cat] || cat}</span></td>
                <td>
                  <span style="font-weight:var(--fw-semibold);color:var(--brand);">${item.total_faltantes}</span>
                  <span style="font-size:11px;color:var(--secondary);"> ocorrencia${item.total_faltantes > 1 ? 's' : ''}</span>
                </td>
                <td style="color:var(--secondary);">${item.total_solicitacoes} pedido${item.total_solicitacoes > 1 ? 's' : ''}</td>
                <td style="min-width:120px;">
                  <div style="background:var(--neutral-200);border-radius:50px;height:6px;overflow:hidden;">
                    <div style="width:${pct}%;height:100%;background:var(--brand);border-radius:50px;transition:width 0.5s;"></div>
                  </div>
                </td>
              </tr>`;
          }).join('')}
        </tbody>
      </table>
    </div>`;
  if (window.lucide) lucide.createIcons();
}

// ---- KPIs ----
function renderKpis(t) {
  if (!t) return;
  const kpis = [
    { label:'Escolas ativas',    val: t.escolas_ativas,       icon:'school',       cls:'stat-icon-dark' },
    { label:'Usuários ativos',   val: t.usuarios_ativos,      icon:'users',        cls:'stat-icon-dark' },
    { label:'Pendentes',         val: t.pendentes,            icon:'clock',        cls:'stat-icon-brand' },
    { label:'Concluídas',        val: t.concluidas,           icon:'check-circle', cls:'stat-icon-green' },
    { label:'Canceladas',        val: t.canceladas,           icon:'x-circle',     cls:'',
      style:'background:var(--neutral-100);color:var(--secondary);' },
    { label:'Item em falta',     val: t.item_em_falta,        icon:'package-x',    cls:'stat-icon-amber' },
    { label:'Total solicitações',val: t.total_solicitacoes,   icon:'list',         cls:'stat-icon-dark' },
    { label:'Sugestões pendentes',val:t.sugestoes_pendentes,  icon:'lightbulb',    cls:'stat-icon-amber' },
  ];

  document.getElementById('kpiGrid').innerHTML = kpis.map(k => `
    <div class="stat-card animate-slide-up">
      <div class="stat-icon ${k.cls}" ${k.style?`style="${k.style}"`:''}>
        <i data-lucide="${k.icon}" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value">${k.val ?? 0}</div>
        <div class="stat-label">${k.label}</div>
      </div>
    </div>`).join('');
  if (window.lucide) lucide.createIcons();
}

// ---- Gráfico de barras — por mês ----
function renderGraficoMes(dados) {
  if (chartMes) chartMes.destroy();
  const ctx = document.getElementById('graficoMes').getContext('2d');
  chartMes = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dados.map(d => d.mes_label),
      datasets: [
        {
          label: 'Total',
          data: dados.map(d => d.total),
          backgroundColor: 'rgba(9,9,11,0.08)',
          borderColor: '#09090B',
          borderWidth: 1.5,
          borderRadius: 6,
        },
        {
          label: 'Concluídas',
          data: dados.map(d => d.concluidas),
          backgroundColor: 'rgba(22,163,74,0.15)',
          borderColor: '#16A34A',
          borderWidth: 1.5,
          borderRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { font: { family:'Roboto', size:11 } } } },
      scales: {
        y: { beginAtZero:true, ticks: { stepSize:1 }, grid: { color:'rgba(0,0,0,0.04)' } },
        x: { grid: { display:false } },
      },
    },
  });
}

// ---- Gráfico de pizza — por status ----
function renderGraficoPizza(dados) {
  if (chartPizza) chartPizza.destroy();
  if (!dados.length) return;

  const ctx = document.getElementById('graficoPizza').getContext('2d');
  chartPizza = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: dados.map(d => STATUS_LABELS[d.status] || d.status),
      datasets: [{
        data: dados.map(d => d.total),
        backgroundColor: dados.map(d => STATUS_COLORS_MAP[d.status] || '#E5E7EB'),
        borderColor:     dados.map(d => STATUS_BORDER_MAP[d.status] || '#9CA3AF'),
        borderWidth: 2,
      }],
    },
    options: {
      responsive:true,
      cutout:'65%',
      plugins:{ legend:{ display:false } },
    },
  });

  const total = dados.reduce((a,d) => a + parseInt(d.total), 0);
  document.getElementById('legendaPizza').innerHTML = dados.map(d => {
    const pct = total ? Math.round(d.total * 100 / total) : 0;
    return `<div style="display:flex;align-items:center;gap:6px;">
      <div style="width:10px;height:10px;border-radius:2px;background:${STATUS_BORDER_MAP[d.status]||'#999'};flex-shrink:0;"></div>
      <span>${STATUS_LABELS[d.status]||d.status}: <b>${d.total}</b> (${pct}%)</span>
    </div>`;
  }).join('');
}

// ---- Tabela de escolas ----
function renderTabelaEscolas(dados) {
  document.getElementById('corpoEscolas').innerHTML = dados.map(r => {
    const pct = r.total > 0 ? Math.round(r.concluidas * 100 / r.total) : 0;
    return `<tr>
      <td style="font-weight:500;">${escHtml(r.escola)}</td>
      <td style="text-align:right;">${r.total}</td>
      <td style="text-align:right;color:#16a34a;font-weight:600;">${r.concluidas}</td>
      <td style="text-align:right;">${r.em_aberto}</td>
      <td style="text-align:right;">
        <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
          <div style="width:50px;height:5px;background:var(--neutral-200);border-radius:3px;overflow:hidden;">
            <div style="width:${pct}%;height:100%;background:${pct>=80?'#16a34a':pct>=50?'#3b82f6':'#f97316'};border-radius:3px;"></div>
          </div>
          <span style="font-size:11px;font-weight:600;">${pct}%</span>
        </div>
      </td>
    </tr>`;
  }).join('') || '<tr><td colspan="5" style="text-align:center;color:var(--secondary);padding:var(--space-6);">Nenhum dado.</td></tr>';
}

// ---- Top produtos ----
function renderTopProdutos(dados) {
  if (!dados.length) {
    document.getElementById('listaTopProdutos').innerHTML = '<p style="color:var(--secondary);font-size:13px;">Nenhum dado de produto.</p>';
    return;
  }
  const maxVal = Math.max(...dados.map(d => parseInt(d.vezes_solicitado)));
  document.getElementById('listaTopProdutos').innerHTML = dados.slice(0,10).map((d, i) => {
    const pct = maxVal > 0 ? Math.round(d.vezes_solicitado * 100 / maxVal) : 0;
    return `<div style="display:flex;align-items:center;gap:var(--space-3);">
      <div style="width:22px;height:22px;border-radius:50%;background:var(--neutral-100);
                  display:flex;align-items:center;justify-content:center;
                  font-size:10px;font-weight:700;color:var(--secondary);flex-shrink:0;">${i+1}</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(d.produto)}</div>
        <div style="height:4px;background:var(--neutral-200);border-radius:2px;margin-top:3px;">
          <div style="width:${pct}%;height:100%;background:var(--brand);border-radius:2px;"></div>
        </div>
      </div>
      <div style="font-size:12px;color:var(--secondary);flex-shrink:0;">
        ${d.vezes_solicitado}x
      </div>
    </div>`;
  }).join('');
}

// ---- Tempo médio ----
function renderTempoMedio(dados) {
  if (!dados.length) {
    document.getElementById('tempoMedio').innerHTML = '<p style="color:var(--secondary);font-size:13px;">Nenhuma solicitação concluída ainda.</p>';
    return;
  }
  const maxDias = Math.max(...dados.map(d => parseFloat(d.dias_media) || 0));
  document.getElementById('tempoMedio').innerHTML = dados.map(d => {
    const dias = parseFloat(d.dias_media) || 0;
    const pct  = maxDias > 0 ? Math.round(dias * 100 / maxDias) : 0;
    const cor  = dias <= 2 ? '#16a34a' : dias <= 5 ? '#3b82f6' : dias <= 10 ? '#f97316' : '#dc2626';
    return `<div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
        <span style="font-size:13px;font-weight:500;">${escHtml(d.escola)}</span>
        <span style="font-size:12px;color:${cor};font-weight:700;">${dias} dias</span>
      </div>
      <div style="height:5px;background:var(--neutral-200);border-radius:3px;">
        <div style="width:${pct}%;height:100%;background:${cor};border-radius:3px;transition:width .5s;"></div>
      </div>
      <div style="font-size:11px;color:var(--secondary);margin-top:2px;">${d.concluidas} concluída(s)</div>
    </div>`;
  }).join('');
}

// ---- Stats QR ----
function renderStatsQr(dados) {
  const QR_LABELS = {
    'sucesso':       ['✓ Sucesso',        '#16a34a', '#dcfce7'],
    'retornado':     ['↩ Retornado',      '#dc2626', '#fee2e2'],
    'item_em_falta': ['⚠ Item em falta',  '#9a3412', '#ffedd5'],
    'erro_token':    ['✗ Erro de token',  '#991b1b', '#fee2e2'],
    'erro_destino':  ['⊗ Destino errado', '#854d0e', '#fef9c3'],
  };
  if (!dados.length) {
    document.getElementById('statsQr').innerHTML = '<p style="color:var(--secondary);font-size:13px;">Nenhuma leitura de QR Code registrada.</p>';
    return;
  }
  const total = dados.reduce((a,d) => a + parseInt(d.total), 0);
  document.getElementById('statsQr').innerHTML = dados.map(d => {
    const [label, cor, bg] = QR_LABELS[d.resultado] || [d.resultado, '#666', '#eee'];
    const pct = total > 0 ? Math.round(d.total * 100 / total) : 0;
    return `<div style="display:flex;justify-content:space-between;align-items:center;
                        padding:var(--space-2) var(--space-3);background:${bg};border-radius:var(--radius-md);">
      <span style="font-size:13px;font-weight:600;color:${cor};">${label}</span>
      <span style="font-size:13px;font-weight:700;color:${cor};">${d.total} <span style="font-weight:400;font-size:11px;">(${pct}%)</span></span>
    </div>`;
  }).join('');
}

// ---- Gráfico rosca categoria ----
function renderGraficoCategoria(dados) {
  if (chartCategoria) chartCategoria.destroy();
  if (!dados.length) return;
  const ctx = document.getElementById('graficoCategoria').getContext('2d');
  chartCategoria = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dados.map(d => d.categoria === 'produto' ? 'Produto' : 'Serviço'),
      datasets: [{
        data: dados.map(d => d.total),
        backgroundColor: dados.map(d => d.categoria === 'produto' ? 'rgba(59,130,246,0.15)' : 'rgba(139,92,246,0.15)'),
        borderColor:     dados.map(d => d.categoria === 'produto' ? '#3B82F6' : '#8B5CF6'),
        borderWidth: 2,
        borderRadius: 8,
      }],
    },
    options: {
      indexAxis:'y',
      responsive:true,
      plugins:{ legend:{ display:false } },
      scales:{ x:{ beginAtZero:true, ticks:{stepSize:1} }, y:{ grid:{display:false} } },
    },
  });
}

// ---- Bloqueados ----
function renderBloqueados(dados) {
  const secao = document.getElementById('secaoBloqueados');
  if (!dados.length) { secao.style.display = 'none'; return; }
  secao.style.display = '';
  document.getElementById('qtdBloqueados').textContent = dados.length;
  const PERFIS = { administrador:'Administrador', diretor:'Diretor(a)', superadmin:'Superadmin' };
  document.getElementById('corpoBloqueados').innerHTML = dados.map(u => `
    <tr>
      <td style="font-weight:500;">${escHtml(u.nome)}</td>
      <td style="font-size:13px;color:var(--secondary);">@${escHtml(u.username)}</td>
      <td><span class="badge badge-analysis">${PERFIS[u.perfil]||u.perfil}</span></td>
      <td style="font-size:13px;">${escHtml(u.escola_nome||'—')}</td>
      <td><span class="badge badge-rejected">${u.tentativas_login} tentativas</span></td>
      <td>
        <a href="/pages/usuarios.php" class="btn btn-outline btn-sm">
          <i data-lucide="key" width="13" height="13"></i> Redefinir senha
        </a>
      </td>
    </tr>`).join('');
  if (window.lucide) lucide.createIcons();
}

function escHtml(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(s||'')));
  return d.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
