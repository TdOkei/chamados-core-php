<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Services\{OportunidadeService, SuperadminService};
use App\Config\App;

AuthMiddleware::requireAdmin();

$pageTitle  = 'Oportunidades';
$activePage = 'oportunidades';
$idAdmin    = Session::getUsuarioId();
$perfil     = Session::getPerfil();
$categoria  = Session::getCategoria();

$service    = new OportunidadeService();
$oports     = $service->listar($perfil === App::PERFIL_SUPERADMIN ? null : $idAdmin);

$saService  = new SuperadminService();
$escolas    = $saService->listarEscolas(apenasAtivas: true);
$produtos   = $saService->listarProdutos(apenasAtivos: true);
$unidades   = $saService->listarUnidades(apenasAtivas: true);

$statusLabels = App::STATUS_LABELS;

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Oportunidades</h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;">
        Crie e gerencie oportunidades para as escolas.
      </p>
    </div>
    <button class="btn btn-primary" onclick="abrirModal()">
      <i data-lucide="plus" width="16" height="16"></i>
      Nova Oportunidade
    </button>
  </div>

  <!-- Lista -->
  <?php if (empty($oports)): ?>
  <div class="card-flat" style="border-radius:var(--radius-xl);">
    <div class="empty-state" style="padding:var(--space-12);">
      <div class="empty-state-icon"><i data-lucide="gift" width="32" height="32"></i></div>
      <p class="empty-state-title">Nenhuma oportunidade criada</p>
      <p class="empty-state-text">Crie oportunidades para enviar às escolas.</p>
    </div>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:var(--space-4);" class="stagger">
    <?php foreach ($oports as $op): ?>
    <div class="card animate-slide-up" style="border-radius:var(--radius-xl);">
      <div style="display:flex;align-items:flex-start;gap:var(--space-4);flex-wrap:wrap;">

        <!-- Info principal -->
        <div style="flex:1;">
          <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-2);">
            <span style="font-family:var(--font-primary);font-weight:var(--fw-bold);font-size:16px;">
              <?= htmlspecialchars($op['titulo']) ?>
            </span>
            <span class="badge badge-<?= $op['categoria'] ?>">
              <?= $op['categoria'] === 'produto' ? 'Produto' : 'Serviço' ?>
            </span>
            <span class="badge <?= $op['status'] === 'ativa' ? 'badge-done' : ($op['status'] === 'desabilitada' ? 'badge-cancelled' : 'badge-rejected') ?>">
              <?= ucfirst($op['status']) ?>
            </span>
            <?php if (!$op['editavel']): ?>
            <span class="badge badge-analysis" title="Alguma escola já tomou decisão">
              <i data-lucide="lock" width="10" height="10"></i> Bloqueada
            </span>
            <?php endif; ?>
          </div>
          <p style="font-size:13px;color:var(--secondary);margin-bottom:var(--space-3);">
            <?= htmlspecialchars(mb_strimwidth($op['descricao'], 0, 100, '...')) ?>
          </p>
          <!-- Progresso das escolas -->
          <div style="display:flex;gap:var(--space-4);font-size:12px;flex-wrap:wrap;">
            <span style="color:var(--secondary);">
              <i data-lucide="school" width="12" height="12" style="display:inline;vertical-align:middle;"></i>
              <?= $op['total_escolas'] ?> escola<?= $op['total_escolas'] != 1 ? 's' : '' ?>
            </span>
            <span style="color:#16a34a;">
              <i data-lucide="check" width="12" height="12" style="display:inline;vertical-align:middle;"></i>
              <?= $op['aceitas'] ?> aceita<?= $op['aceitas'] != 1 ? 's' : '' ?>
            </span>
            <span style="color:var(--brand);">
              <i data-lucide="x" width="12" height="12" style="display:inline;vertical-align:middle;"></i>
              <?= $op['rejeitadas'] ?> rejeitada<?= $op['rejeitadas'] != 1 ? 's' : '' ?>
            </span>
            <span style="color:var(--secondary);">
              <i data-lucide="clock" width="12" height="12" style="display:inline;vertical-align:middle;"></i>
              <?= $op['pendentes'] ?> pendente<?= $op['pendentes'] != 1 ? 's' : '' ?>
            </span>
          </div>
        </div>

        <!-- Ações -->
        <div style="display:flex;flex-direction:column;gap:var(--space-2);">
          <button class="btn btn-ghost btn-sm" onclick="verDetalhes(<?= $op['id'] ?>)">
            <i data-lucide="eye" width="14" height="14"></i> Detalhes
          </button>
          <?php if ($op['editavel'] && $op['status'] === 'ativa'): ?>
          <button class="btn btn-ghost btn-sm" onclick="editarOportunidade(<?= $op['id'] ?>)">
            <i data-lucide="pencil" width="14" height="14"></i> Editar
          </button>
          <?php endif; ?>
          <?php if ($op['status'] === 'ativa'): ?>
          <button class="btn btn-ghost btn-sm" onclick="desabilitar(<?= $op['id'] ?>)"
                  style="color:var(--secondary);">
            <i data-lucide="eye-off" width="14" height="14"></i> Desabilitar
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ====== MODAL CRIAR/EDITAR ====== -->
<div class="modal-overlay" id="modalOport" style="display:none;" onclick="fecharModalFora(event,'modalOport')">
  <div class="modal" style="max-width:600px;max-height:90vh;overflow-y:auto;">
    <div class="modal-header" style="position:sticky;top:0;background:var(--white);z-index:1;">
      <h3 class="modal-title" id="oTitulo">Nova Oportunidade</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal('modalOport')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <div id="oAlert" style="display:none;" class="mb-4"></div>
      <input type="hidden" id="oId">

      <div class="form-group">
        <label class="form-label">Título <span class="required">*</span></label>
        <input type="text" id="oTituloInput" class="form-control" placeholder="Título da oportunidade" maxlength="150">
      </div>

      <div class="form-group">
        <label class="form-label">Categoria <span class="required">*</span></label>
        <select id="oCategoria" class="form-control" onchange="toggleCategoriaOport()">
          <option value="">Selecione...</option>
          <?php if ($perfil === App::PERFIL_SUPERADMIN || $categoria === 'produto'): ?>
          <option value="produto">Produto</option>
          <?php endif; ?>
          <?php if ($perfil === App::PERFIL_SUPERADMIN || $categoria === 'servico'): ?>
          <option value="servico">Serviço</option>
          <?php endif; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Descrição <span class="required">*</span></label>
        <textarea id="oDescricao" class="form-control" rows="3" maxlength="400"
                  placeholder="Descreva a oportunidade..."
                  data-maxlength="400" data-counter="cntODesc"></textarea>
        <div class="char-counter" id="cntODesc">0/400</div>
      </div>

      <!-- Pedir quantidade ao diretor -->
      <div id="secaoPedirQuantidade" style="display:none;">
        <label class="form-check" style="padding:var(--space-4);background:var(--neutral-100);border-radius:var(--radius-lg);cursor:pointer;margin-bottom:var(--space-3);">
          <input type="checkbox" class="form-check-input" id="oPedirQuantidade" onchange="togglePedirQuantidade()">
          <div>
            <div class="form-check-label" style="font-weight:var(--fw-semibold);">Deixar disponivel campo de Quantidade</div>
            <div style="font-size:12px;color:var(--secondary);margin-top:2px;">O diretor informara a quantidade desejada ao aceitar a oportunidade</div>
          </div>
        </label>
        <div id="secaoUnidadeQuantidade" style="display:none;" class="form-group">
          <label class="form-label">Unidade de medida <span class="required">*</span></label>
          <select id="oUnidadeQuantidade" class="form-control">
            <option value="">Selecione...</option>
            <?php foreach ($unidades as $un): ?>
            <option value="<?= $un['id'] ?>"><?= htmlspecialchars($un['nome']) ?> (<?= htmlspecialchars($un['sigla']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Itens de produto -->
      <div id="secaoItensProduto" style="display:none;">
        <div class="divider"></div>
        <div class="d-flex align-center justify-between mb-3">
          <label class="form-label" style="margin:0;">Itens <span class="required">*</span></label>
          <button type="button" class="btn btn-outline btn-sm" onclick="adicionarItemOport()">
            <i data-lucide="plus" width="14" height="14"></i> Adicionar item
          </button>
        </div>
        <div id="itensProdutoOport" style="display:flex;flex-direction:column;gap:var(--space-3);"></div>
      </div>

      <div class="divider"></div>

      <!-- Escolas -->
      <div class="form-group">
        <label class="form-label">Escolas destinatárias <span class="required">*</span></label>
        <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2);">
          <span style="font-size:12px;color:var(--secondary);">Selecione uma ou mais escolas</span>
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleTodasEscolas()" style="font-size:12px;">Selecionar todas</button>
        </div>
        <div style="max-height:200px;overflow-y:auto;border:1.5px solid var(--neutral-200);border-radius:var(--radius-md);padding:var(--space-3);">
          <?php foreach ($escolas as $esc): ?>
          <label class="form-check" style="padding:6px 0;border-bottom:1px solid var(--neutral-100);">
            <input type="checkbox" class="form-check-input escola-check" value="<?= $esc['id'] ?>">
            <span class="form-check-label">
              <?= htmlspecialchars($esc['nome']) ?>
              <?php if ($esc['diretor_nome']): ?>
                <span style="font-size:11px;color:var(--secondary);"> — <?= htmlspecialchars($esc['diretor_nome']) ?></span>
              <?php endif; ?>
            </span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="modal-footer" style="position:sticky;bottom:0;background:var(--white);">
      <button class="btn btn-outline" onclick="fecharModal('modalOport')">Cancelar</button>
      <button class="btn btn-primary" id="btnSalvarOport" onclick="salvarOportunidade()">Salvar</button>
    </div>
  </div>
</div>

<!-- ====== MODAL DETALHES ====== -->
<div class="modal-overlay" id="modalDetOport" style="display:none;" onclick="fecharModalFora(event,'modalDetOport')">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h3 class="modal-title">Detalhes da Oportunidade</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal('modalDetOport')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body" id="detOportBody">
      <div style="text-align:center;padding:var(--space-8);">
        <span class="spinner spinner-brand" style="width:28px;height:28px;"></span>
      </div>
    </div>
  </div>
</div>

<script>
const produtos = <?= json_encode(array_values($produtos)) ?>;
const unidades = <?= json_encode(array_values($unidades)) ?>;
let contadorItem = 0;
let modoEdicao   = false;

function abrirModal() {
  modoEdicao = false;
  document.getElementById('oId').value = '';
  document.getElementById('oTituloInput').value = '';
  document.getElementById('oCategoria').value = '';
  document.getElementById('oDescricao').value  = '';
  document.getElementById('oAlert').style.display = 'none';
  const elPedirQtd = document.getElementById('oPedirQuantidade');
  if (elPedirQtd) elPedirQtd.checked = false;
  const elUnidadeQtd = document.getElementById('oUnidadeQuantidade');
  if (elUnidadeQtd) elUnidadeQtd.value = '';
  const elSecaoUnidade = document.getElementById('secaoUnidadeQuantidade');
  if (elSecaoUnidade) elSecaoUnidade.style.display = 'none';
  document.querySelectorAll('.escola-check').forEach(c => c.checked = false);
  document.getElementById('itensProdutoOport').innerHTML = '';
  document.getElementById('oTitulo').textContent = 'Nova Oportunidade';
  toggleCategoriaOport();
  document.getElementById('modalOport').style.display = 'flex';
  document.getElementById('oDescricao').dispatchEvent(new Event('input'));
}

async function editarOportunidade(id) {
  modoEdicao = true;
  document.getElementById('oId').value = id;
  document.getElementById('oTitulo').textContent = 'Editar Oportunidade';
  document.getElementById('oAlert').style.display = 'none';
  document.getElementById('modalOport').style.display = 'flex';

  const res  = await fetch(`/api/oportunidades/index.php?id=${id}`);
  const data = await res.json();
  if (!data.success) return;

  const op = data.data.oportunidade;
  document.getElementById('oTituloInput').value = op.titulo;
  document.getElementById('oCategoria').value   = op.categoria;
  document.getElementById('oDescricao').value   = op.descricao;
  document.getElementById('oDescricao').dispatchEvent(new Event('input'));
  toggleCategoriaOport();

  if (op.categoria === 'produto' && op.itens) {
    op.itens.forEach(i => adicionarItemOport(i.produto_id, i.id_unidade, i.quantidade));
  }
}

function toggleCategoriaOport() {
  const cat = document.getElementById('oCategoria').value;
  const isProduto = cat === 'produto';
  document.getElementById('secaoItensProduto').style.display = isProduto ? '' : 'none';
  document.getElementById('secaoPedirQuantidade').style.display = isProduto ? '' : 'none';
  if (!isProduto) {
    const _pq = document.getElementById('oPedirQuantidade');
    if (_pq) _pq.checked = false;
    const _su = document.getElementById('secaoUnidadeQuantidade');
    if (_su) _su.style.display = 'none';
  }
}

function toggleTodasEscolas() {
  const checks = document.querySelectorAll('.escola-check');
  const allChecked = Array.from(checks).every(c => c.checked);
  checks.forEach(c => c.checked = !allChecked);
}

function adicionarItemOport(idProd = '', idUn = '', qtd = '') {
  const prodOptions = produtos.map(p =>
    `<option value="${p.id}" ${p.id == idProd ? 'selected' : ''  }>${p.nome}</option>`
  ).join('');
  const unOptions = unidades.map(u =>
    `<option value="${u.id}" ${u.id == idUn ? 'selected' : ''  }>${u.nome} (${u.sigla})</option>`
  ).join('');

  const div = document.createElement('div');
  const idx = contadorItem++;
  div.id = `item-oport-${idx}`;
  div.style.cssText = 'display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:end;';
  div.innerHTML = `
    <div>
      <label class="form-label" style="font-size:12px;">Produto</label>
      <select class="form-control item-produto-oport" style="font-size:13px;">
        <option value="">Selecione...</option>${prodOptions}
      </select>
    </div>
    <div style="width:180px;">
      <label class="form-label" style="font-size:12px;">Qtd / Un</label>
      <div style="display:flex;gap:4px;">
        <input type="number" min="0.01" step="0.01" value="${qtd}" placeholder="0"
               class="form-control item-qtd-oport" style="font-size:14px;width:80px;">
        <select class="form-control item-un-oport" style="font-size:13px;width:90px;">
          <option value="">Selecione</option>${unOptions}
        </select>
      </div>
    </div>
    <button type="button" class="btn btn-ghost btn-icon-sm" onclick="document.getElementById('item-oport-${idx}').remove()" style="margin-bottom:0;">
      <i data-lucide="trash-2" width="14" height="14" style="color:var(--brand);"></i>
    </button>
  `;
  document.getElementById('itensProdutoOport').appendChild(div);
  if (window.lucide) lucide.createIcons();
}

async function salvarOportunidade() {
  const id        = document.getElementById('oId').value;
  const titulo    = document.getElementById('oTituloInput').value.trim();
  const categoria = document.getElementById('oCategoria').value;
  const descricao = document.getElementById('oDescricao').value.trim();
  const escolas   = Array.from(document.querySelectorAll('.escola-check:checked')).map(c => parseInt(c.value));

  document.getElementById('oAlert').style.display = 'none';
  if (!titulo || !categoria || !descricao) return showOAlert('Preencha todos os campos obrigatórios.','danger');

  let itens = [];
  if (categoria === 'produto') {
    document.querySelectorAll('#itensProdutoOport > div').forEach(div => {
      const prod = div.querySelector('.item-produto-oport')?.value;
      const qtd  = div.querySelector('.item-qtd-oport')?.value;
      const un   = div.querySelector('.item-un-oport')?.value;
      if (prod && qtd && un) itens.push({ id_produto: prod, id_unidade: un, quantidade: qtd });
    });
    if (!itens.length) return showOAlert('Adicione pelo menos um item.','danger');
  }

  if (!modoEdicao && !escolas.length) return showOAlert('Selecione pelo menos uma escola.','danger');

  const btn = document.getElementById('btnSalvarOport');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';

  const _pqEl   = document.getElementById('oPedirQuantidade');
  const pedirQtd = _pqEl ? _pqEl.checked : false;
  const _uqEl    = document.getElementById('oUnidadeQuantidade');
  const payload = {
    titulo,
    categoria,
    descricao,
    pedir_quantidade:      pedirQtd ? 1 : 0,
    id_unidade_quantidade: (pedirQtd && _uqEl) ? _uqEl.value : null,
    itens,
    escolas,
  };

  try {
    const method = modoEdicao ? 'PUT' : 'POST';
    const url    = modoEdicao
      ? `/api/oportunidades/index.php?id=${id}`
      : '/api/oportunidades/index.php';

    const res  = await fetch(url, { method, headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const data = await res.json();

    if (data.success) { fecharModal('modalOport'); toast(data.message,'success'); setTimeout(()=>location.reload(),900); }
    else showOAlert(data.message,'danger');
  } catch(e) { showOAlert('Erro de conexão.','danger'); }
  finally { btn.disabled=false; btn.textContent='Salvar'; }
}

async function verDetalhes(id) {
  document.getElementById('modalDetOport').style.display = 'flex';
  document.getElementById('detOportBody').innerHTML = '<div style="text-align:center;padding:var(--space-8);"><span class="spinner spinner-brand" style="width:28px;height:28px;"></span></div>';

  const res  = await fetch(`/api/oportunidades/index.php?id=${id}`);
  const data = await res.json();
  if (!data.success) return;
  const op = data.data.oportunidade;

  const decisaoClass = { aceita:'badge-done', rejeitada:'badge-rejected', pendente:'badge-pending' };
  const escolasHtml  = (op.escolas || []).map(e => `
    <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--neutral-100);">
      <span style="font-size:13px;">${e.escola_nome}</span>
      <span class="badge ${decisaoClass[e.decisao] || ''}">${e.decisao}</span>
    </div>`).join('');

  document.getElementById('detOportBody').innerHTML = `
    <p style="font-size:14px;margin-bottom:var(--space-4);">${op.descricao}</p>
    <div class="form-label mb-2">Escolas e decisões</div>
    ${escolasHtml || '<p style="font-size:13px;color:var(--secondary);">Nenhuma escola vinculada.</p>'}
  `;
  if (window.lucide) lucide.createIcons();
}

async function desabilitar(id) {
  if (!confirm('Desabilitar esta oportunidade? Quem já decidiu mantém seu histórico.')) return;
  const res  = await fetch(`/api/oportunidades/index.php?id=${id}&acao=desabilitar`, {method:'POST'});
  const data = await res.json();
  if (data.success) { toast(data.message,'success'); setTimeout(()=>location.reload(),900); }
  else toast(data.message,'danger');
}

function fecharModal(id)  { document.getElementById(id).style.display = 'none'; }
function fecharModalFora(e,id) { if(e.target.id===id) fecharModal(id); }
function showOAlert(msg,tipo) {
  const el=document.getElementById('oAlert');
  el.className=`alert alert-${tipo}`;
  el.innerHTML=`<i data-lucide="alert-circle" width="16" height="16"></i> ${msg}`;
  el.style.display='flex';
  if(window.lucide) lucide.createIcons();
}
function toast(msg,tipo) {
  const t=document.createElement('div');
  t.className=`alert alert-${tipo} animate-slide-up`;
  t.style.cssText='position:fixed;bottom:24px;right:24px;z-index:9999;max-width:340px;box-shadow:var(--shadow-xl);';
  t.innerHTML=`<i data-lucide="${tipo==='success'?'check-circle':'alert-circle'}" width="16" height="16"></i> ${msg}`;
  document.body.appendChild(t);
  if(window.lucide) lucide.createIcons();
  setTimeout(()=>t.remove(),3500);
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
