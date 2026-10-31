<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireSuperadmin();

$pageTitle  = 'Auditoria';
$activePage = 'auditoria';

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Log de Auditoria</h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;">
        Registro de todas as ações administrativas do sistema.
      </p>
    </div>
    <button class="btn btn-outline btn-sm" onclick="exportarPDF()">
      <i data-lucide="file-down" width="14" height="14"></i> Exportar PDF
    </button>
  </div>

  <!-- Abas -->
  <div style="display:flex;gap:var(--space-2);margin-bottom:var(--space-5);background:var(--neutral-100);padding:4px;border-radius:var(--radius-lg);width:fit-content;">
    <button class="tab-btn-auditoria active" id="tabBtnAcoes" onclick="mudarAbaAuditoria('acoes')">
      <i data-lucide="list" width="14" height="14"></i> Ações Administrativas
    </button>
    <button class="tab-btn-auditoria" id="tabBtnLogins" onclick="mudarAbaAuditoria('logins')">
      <i data-lucide="key" width="14" height="14"></i> Histórico de Logins
    </button>
  </div>

  <style>
    .tab-btn-auditoria {
      background:none; border:none; padding:8px 16px;
      font-family:var(--font-primary); font-size:13px; font-weight:var(--fw-semibold);
      color:var(--secondary); border-radius:var(--radius-md); cursor:pointer;
      transition:var(--transition); display:flex; align-items:center; gap:6px;
    }
    .tab-btn-auditoria.active { background:var(--white); color:var(--primary); box-shadow:var(--shadow-sm); }
  </style>

  <!-- ABA: ACOES ADMINISTRATIVAS -->
  <div id="abaAcoes">

  <!-- Filtros -->
  <div class="card mb-6" style="padding:var(--space-4);">
    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end;">
      <div style="flex:1;min-width:140px;">
        <label class="form-label">Ação</label>
        <select id="filtroAcao" class="form-control" onchange="carregar(1)">
          <option value="">Todas</option>
          <option value="criar">Criar</option>
          <option value="editar">Editar</option>
          <option value="ativar">Ativar</option>
          <option value="desativar">Desativar</option>
          <option value="status">Alterar Status</option>
          <option value="cancelar">Cancelar</option>
          <option value="exportar">Exportar</option>
        </select>
      </div>
      <div style="flex:1;min-width:140px;">
        <label class="form-label">Entidade</label>
        <select id="filtroEntidade" class="form-control" onchange="carregar(1)">
          <option value="">Todas</option>
          <option value="usuario">Usuário</option>
          <option value="escola">Escola</option>
          <option value="produto">Produto</option>
          <option value="solicitacao">Solicitação</option>
          <option value="oportunidade">Oportunidade</option>
          <option value="relatorio">Exportação</option>
        </select>
      </div>
      <div style="flex:1;min-width:140px;">
        <label class="form-label">Usuário</label>
        <select id="filtroUsuario" class="form-control" onchange="carregar(1)">
          <option value="">Todos</option>
        </select>
      </div>
      <div style="min-width:130px;">
        <label class="form-label">De</label>
        <input type="date" id="filtroDe" class="form-control" onchange="carregar(1)">
      </div>
      <div style="min-width:130px;">
        <label class="form-label">Ate</label>
        <input type="date" id="filtroAte" class="form-control" onchange="carregar(1)">
      </div>
      <button class="btn btn-outline btn-sm" onclick="limparFiltros()">
        <i data-lucide="x" width="14" height="14"></i> Limpar
      </button>
    </div>
  </div>

  <!-- Tabela -->
  <div class="card" style="border-radius:var(--radius-xl);">
    <div id="loadingAuditoria" style="text-align:center;padding:var(--space-10);">
      <span class="spinner spinner-brand" style="width:28px;height:28px;"></span>
    </div>
    <div id="conteudoAuditoria" style="display:none;">
      <div class="table-wrapper" style="box-shadow:none;">
        <table class="table">
          <thead>
            <tr>
              <th>Data/Hora</th>
              <th>Usuário</th>
              <th>Perfil</th>
              <th>Ação</th>
              <th>Entidade</th>
              <th>Descrição</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody id="tbodyAuditoria"></tbody>
        </table>
      </div>
      <div id="paginacao" style="display:flex;justify-content:center;gap:var(--space-2);padding:var(--space-4);"></div>
    </div>
  </div>

  </div>
  <!-- FIM ABA: ACOES ADMINISTRATIVAS -->

  <!-- ABA: HISTORICO DE LOGINS -->
  <div id="abaLogins" style="display:none;">

    <div class="card mb-6" style="padding:var(--space-4);">
      <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
          <label class="form-label">Usuario</label>
          <select id="filtroLoginUsuario" class="form-control" onchange="carregarLogins(1)">
            <option value="">Todos</option>
          </select>
        </div>
        <div style="min-width:130px;">
          <label class="form-label">De</label>
          <input type="date" id="filtroLoginDe" class="form-control" onchange="carregarLogins(1)">
        </div>
        <div style="min-width:130px;">
          <label class="form-label">Ate</label>
          <input type="date" id="filtroLoginAte" class="form-control" onchange="carregarLogins(1)">
        </div>
        <button class="btn btn-outline btn-sm" onclick="limparFiltrosLogin()">
          <i data-lucide="x" width="14" height="14"></i> Limpar
        </button>
      </div>
    </div>

    <div class="card" style="border-radius:var(--radius-xl);">
      <div id="loadingLogins" style="text-align:center;padding:var(--space-10);">
        <span class="spinner spinner-brand" style="width:28px;height:28px;"></span>
      </div>
      <div id="conteudoLogins" style="display:none;">
        <div class="table-wrapper" style="box-shadow:none;">
          <table class="table">
            <thead>
              <tr>
                <th>Data/Hora</th>
                <th>Usuario</th>
                <th>Perfil</th>
                <th>Resultado</th>
                <th>Detalhes</th>
                <th>IP</th>
              </tr>
            </thead>
            <tbody id="tbodyLogins"></tbody>
          </table>
        </div>
        <div id="paginacaoLogins" style="display:flex;justify-content:center;gap:var(--space-2);padding:var(--space-4);"></div>
      </div>
    </div>

  </div>
  <!-- FIM ABA: HISTORICO DE LOGINS -->

</div>

<script>
let paginaLoginAtual = 1;
let abaAuditoriaAtual = 'acoes';

const resultadoBadge = {
  login_sucesso:   { cls: 'badge-done',     label: 'Sucesso',  icon: 'check-circle' },
  login_falha:     { cls: 'badge-rejected', label: 'Falha',    icon: 'x-circle' },
  conta_bloqueada: { cls: 'badge-cancelled',label: 'Bloqueada',icon: 'lock' },
};

function mudarAbaAuditoria(aba) {
  abaAuditoriaAtual = aba;
  document.getElementById('abaAcoes').style.display  = aba === 'acoes'  ? '' : 'none';
  document.getElementById('abaLogins').style.display  = aba === 'logins' ? '' : 'none';
  document.getElementById('tabBtnAcoes').classList.toggle('active', aba === 'acoes');
  document.getElementById('tabBtnLogins').classList.toggle('active', aba === 'logins');
  if (aba === 'logins' && !document.getElementById('filtroLoginUsuario').dataset.carregado) {
    carregarLogins(1);
  }
}

async function carregarLogins(pagina = 1) {
  paginaLoginAtual = pagina;
  document.getElementById('loadingLogins').style.display  = '';
  document.getElementById('conteudoLogins').style.display = 'none';

  const params = new URLSearchParams({
    pagina,
    id_usuario: document.getElementById('filtroLoginUsuario').value,
    de:         document.getElementById('filtroLoginDe').value,
    ate:        document.getElementById('filtroLoginAte').value,
  });

  const res  = await fetch('/api/auditoria/logins.php?' + params);
  const data = await res.json();
  if (!data.success) return;

  const selUser = document.getElementById('filtroLoginUsuario');
  const valAtual = selUser.value;
  selUser.innerHTML = '<option value="">Todos</option>' +
    data.data.usuarios.map(u => `<option value="${u.id_usuario}" ${String(u.id_usuario) === valAtual ? 'selected' : ''}>${u.nome}</option>`).join('');
  selUser.dataset.carregado = '1';

  const tbody = document.getElementById('tbodyLogins');
  if (!data.data.logs.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--secondary);padding:var(--space-8);">Nenhum registro encontrado.</td></tr>';
  } else {
    tbody.innerHTML = data.data.logs.map(log => {
      const r     = resultadoBadge[log.acao] || { cls: 'badge-pending', label: log.acao, icon: 'circle' };
      const dt    = new Date(log.created_at.replace(' ', 'T'));
      const dtStr = dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
      return `<tr>
        <td style="font-size:12px;white-space:nowrap;">${dtStr}</td>
        <td style="font-weight:var(--fw-medium);">${log.usuario_nome || 'Desconhecido'}</td>
        <td><span style="font-size:11px;color:var(--secondary);">${log.perfil}</span></td>
        <td><span class="badge ${r.cls}" style="font-size:11px;">${r.label}</span></td>
        <td style="font-size:13px;max-width:320px;">${log.descricao}</td>
        <td style="font-size:11px;color:var(--secondary);">${log.ip || '—'}</td>
      </tr>`;
    }).join('');
  }

  const total   = data.data.total;
  const limite  = data.data.limite;
  const paginas = Math.ceil(total / limite);
  const pag     = document.getElementById('paginacaoLogins');

  if (paginas <= 1) {
    pag.innerHTML = '';
  } else {
    let btns = '';
    for (let i = 1; i <= paginas; i++) {
      btns += `<button class="btn btn-sm ${i === pagina ? 'btn-primary' : 'btn-outline'}" onclick="carregarLogins(${i})">${i}</button>`;
    }
    pag.innerHTML = btns;
  }

  document.getElementById('loadingLogins').style.display  = 'none';
  document.getElementById('conteudoLogins').style.display = '';
  if (window.lucide) lucide.createIcons();
}

function limparFiltrosLogin() {
  ['filtroLoginUsuario','filtroLoginDe','filtroLoginAte'].forEach(id => document.getElementById(id).value = '');
  carregarLogins(1);
}
let paginaAtual = 1;

const acaoBadge = {
  criar:     { cls: 'badge-done',       label: 'Criar' },
  editar:    { cls: 'badge-analysis',   label: 'Editar' },
  ativar:    { cls: 'badge-dispatched', label: 'Ativar' },
  desativar: { cls: 'badge-cancelled',  label: 'Desativar' },
  status:    { cls: 'badge-processing', label: 'Alt. Status' },
  cancelar:  { cls: 'badge-rejected',   label: 'Cancelar' },
  exportar:  { cls: 'badge-pending',    label: 'Exportar' },
};

const entidadeLabel = {
  usuario: 'Usuário', escola: 'Escola', produto: 'Produto',
  solicitacao: 'Solicitação', oportunidade: 'Oportunidade', relatorio: 'Exportação',
};

async function carregar(pagina = 1) {
  paginaAtual = pagina;
  document.getElementById('loadingAuditoria').style.display  = '';
  document.getElementById('conteudoAuditoria').style.display = 'none';

  const params = new URLSearchParams({
    pagina,
    acao:       document.getElementById('filtroAcao').value,
    entidade:   document.getElementById('filtroEntidade').value,
    id_usuario: document.getElementById('filtroUsuario').value,
    de:         document.getElementById('filtroDe').value,
    ate:        document.getElementById('filtroAte').value,
  });

  const res  = await fetch('/api/auditoria/index.php?' + params);
  const data = await res.json();
  if (!data.success) return;

  // Popula select de usuarios
  const selUser = document.getElementById('filtroUsuario');
  const valAtual = selUser.value;
  selUser.innerHTML = '<option value="">Todos</option>' +
    data.data.usuarios.map(u => `<option value="${u.id_usuario}" ${u.id_usuario == valAtual ? 'selected' : ''}>${u.nome}</option>`).join('');

  // Popula tabela
  const tbody = document.getElementById('tbodyAuditoria');
  if (!data.data.logs.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--secondary);padding:var(--space-8);">Nenhum registro encontrado.</td></tr>';
  } else {
    tbody.innerHTML = data.data.logs.map(log => {
      const badge = acaoBadge[log.acao] || { cls: 'badge-pending', label: log.acao };
      const dt    = new Date(log.created_at.replace(' ', 'T'));
      const dtStr = dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
      return `<tr>
        <td style="font-size:12px;white-space:nowrap;">${dtStr}</td>
        <td style="font-weight:var(--fw-medium);">${log.usuario_nome || '—'}</td>
        <td><span style="font-size:11px;color:var(--secondary);">${log.perfil}</span></td>
        <td><span class="badge ${badge.cls}" style="font-size:11px;">${badge.label}</span></td>
        <td style="font-size:12px;color:var(--secondary);">${entidadeLabel[log.entidade] || log.entidade}${log.id_entidade ? ' #'+log.id_entidade : ''}</td>
        <td style="font-size:13px;max-width:300px;">${log.descricao}</td>
        <td style="font-size:11px;color:var(--secondary);">${log.ip || '—'}</td>
      </tr>`;
    }).join('');
  }

  // Paginacao
  const total   = data.data.total;
  const limite  = data.data.limite;
  const paginas = Math.ceil(total / limite);
  const pag     = document.getElementById('paginacao');

  if (paginas <= 1) {
    pag.innerHTML = '';
  } else {
    let btns = '';
    for (let i = 1; i <= paginas; i++) {
      btns += `<button class="btn btn-sm ${i === pagina ? 'btn-primary' : 'btn-outline'}"
        onclick="carregar(${i})">${i}</button>`;
    }
    pag.innerHTML = btns;
  }

  document.getElementById('loadingAuditoria').style.display  = 'none';
  document.getElementById('conteudoAuditoria').style.display = '';
}

function limparFiltros() {
  ['filtroAcao','filtroEntidade','filtroUsuario','filtroDe','filtroAte']
    .forEach(id => document.getElementById(id).value = '');
  carregar(1);
}

function exportarPDF() {
  const acao     = document.getElementById('filtroAcao').value;
  const entidade = document.getElementById('filtroEntidade').value;
  const usuario  = document.getElementById('filtroUsuario').options[document.getElementById('filtroUsuario').selectedIndex]?.text || '';
  const de       = document.getElementById('filtroDe').value;
  const ate      = document.getElementById('filtroAte').value;

  // Monta filtros aplicados para exibir no cabecalho do PDF
  const filtrosTexto = [];
  if (acao)     filtrosTexto.push('Acao: ' + acao);
  if (entidade) filtrosTexto.push('Entidade: ' + entidade);
  if (usuario && usuario !== 'Todos') filtrosTexto.push('Usuario: ' + usuario);
  if (de)       filtrosTexto.push('De: ' + de);
  if (ate)      filtrosTexto.push('Ate: ' + ate);

  // Captura linhas da tabela atual
  const rows = document.querySelectorAll('#tbodyAuditoria tr');
  let tabelaHtml = '';
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    if (!cells.length) return;
    tabelaHtml += '<tr>';
    cells.forEach((td, i) => {
      // Remove badges HTML, pega só o texto
      tabelaHtml += `<td>${td.innerText}</td>`;
    });
    tabelaHtml += '</tr>';
  });

  const janela = window.open('', '_blank');
  janela.document.write(`
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <title>Log de Auditoria - Td Okei</title>
      <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; padding: 20px; }
        .cabecalho { margin-bottom: 20px; border-bottom: 2px solid #e53e3e; padding-bottom: 12px; }
        .cabecalho h1 { font-size: 18px; color: #e53e3e; margin-bottom: 4px; }
        .cabecalho p  { font-size: 11px; color: #666; }
        .filtros { margin-bottom: 12px; font-size: 11px; color: #444; }
        .filtros span { background: #f3f3f3; border-radius: 4px; padding: 2px 8px; margin-right: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #111; color: #fff; padding: 7px 8px; text-align: left; font-size: 10px; letter-spacing: 0.05em; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .rodape { margin-top: 16px; font-size: 10px; color: #999; text-align: right; }
        @media print {
          body { padding: 10px; }
          button { display: none; }
        }
      </style>
    </head>
    <body>
      <div class="cabecalho">
        <h1>Td Okei — Log de Auditoria</h1>
        <p>Gerado em ${new Date().toLocaleDateString('pt-BR')} as ${new Date().toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'})}</p>
      </div>
      ${filtrosTexto.length ? `<div class="filtros">Filtros: ${filtrosTexto.map(f => `<span>${f}</span>`).join('')}</div>` : ''}
      <table>
        <thead>
          <tr>
            <th>Data/Hora</th>
            <th>Usuario</th>
            <th>Perfil</th>
            <th>Acao</th>
            <th>Entidade</th>
            <th>Descricao</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>${tabelaHtml}</tbody>
      </table>
      <div class="rodape">Td Okei &copy; — Uma empresa Capelense</div>
      <script>window.onload = function() { window.print(); }<\/script>
    </body>
    </html>
  `);
  janela.document.close();
}

carregar(1);
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
