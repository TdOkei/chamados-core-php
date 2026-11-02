<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Services\SuperadminService;

AuthMiddleware::requireDiretor();

$pageTitle  = 'Nova Solicitação';
$activePage = 'nova-solicitacao';

$saService = new SuperadminService();
$produtos  = $saService->listarProdutos(apenasAtivos: true);
$unidades  = $saService->listarUnidades(apenasAtivas: true);

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="mb-6">
    <h2 class="text-h2">Nova Solicitação</h2>
    <p class="text-body" style="color:var(--secondary);margin-top:4px;">
      Solicite produtos ou serviços para sua escola junto à Secretaria de Ensino.
    </p>
  </div>

  <div id="formAlert" style="display:none;" class="mb-4"></div>

  <!-- PASSO 1: Categoria -->
  <div id="stepCategoria">
    <div class="card-flat" style="border-radius:var(--radius-xl);padding:var(--space-6);">
      <p class="form-label" style="font-size:15px;margin-bottom:var(--space-5);">
        Qual tipo de solicitação você deseja fazer?
      </p>
      <div class="grid-2" style="gap:var(--space-4);">

        <button onclick="selecionarCategoria('produto')"
          style="padding:var(--space-6);border-radius:var(--radius-xl);border:2px solid var(--neutral-200);
                 background:var(--white);cursor:pointer;transition:var(--transition);text-align:left;
                 display:flex;flex-direction:column;gap:var(--space-3);" class="card-sel">
          <div style="width:48px;height:48px;border-radius:var(--radius-md);background:var(--brand-light);
                      display:flex;align-items:center;justify-content:center;color:var(--brand);">
            <i data-lucide="package" width="24" height="24"></i>
          </div>
          <div>
            <div style="font-family:var(--font-primary);font-weight:var(--fw-bold);font-size:16px;margin-bottom:4px;">Produto</div>
            <div style="font-size:13px;color:var(--secondary);">Materiais, equipamentos ou suprimentos. Você pode adicionar múltiplos itens.</div>
          </div>
        </button>

        <button onclick="selecionarCategoria('servico')"
          style="padding:var(--space-6);border-radius:var(--radius-xl);border:2px solid var(--neutral-200);
                 background:var(--white);cursor:pointer;transition:var(--transition);text-align:left;
                 display:flex;flex-direction:column;gap:var(--space-3);" class="card-sel">
          <div style="width:48px;height:48px;border-radius:var(--radius-md);background:var(--neutral-100);
                      display:flex;align-items:center;justify-content:center;color:var(--secondary);">
            <i data-lucide="wrench" width="24" height="24"></i>
          </div>
          <div>
            <div style="font-family:var(--font-primary);font-weight:var(--fw-bold);font-size:16px;margin-bottom:4px;">Serviço</div>
            <div style="font-size:13px;color:var(--secondary);">Manutenção, reparo ou instalação. Uma descrição por solicitação.</div>
          </div>
        </button>
      </div>
    </div>
  </div>

  <!-- PASSO 2A: Produto -->
  <div id="formProduto" style="display:none;">
    <div class="d-flex align-center gap-3 mb-5">
      <button class="btn btn-ghost btn-icon-sm" onclick="voltarCategoria()">
        <i data-lucide="arrow-left" width="16" height="16"></i>
      </button>
      <div>
        <span class="badge badge-produto">Produto</span>
        <h3 style="font-family:var(--font-primary);font-size:18px;font-weight:var(--fw-bold);margin-top:4px;">Solicitação de Produtos</h3>
      </div>
    </div>

    <div class="card" style="border-radius:var(--radius-xl);">
      <div class="d-flex align-center justify-between mb-4">
        <div class="card-title">Itens do pedido</div>
        <button type="button" class="btn btn-outline btn-sm" onclick="adicionarItem()">
          <i data-lucide="plus" width="14" height="14"></i> Adicionar item
        </button>
      </div>
      <div id="listaItens" style="display:flex;flex-direction:column;gap:var(--space-4);"></div>
      <div class="divider mt-4"></div>
      <div class="d-flex justify-between align-center mt-4 flex-wrap gap-3">
        <p style="font-size:13px;color:var(--secondary);">Máximo 30 itens por solicitação.</p>
        <button class="btn btn-primary" id="btnEnviarProduto" onclick="enviarSolicitacao('produto')">
          <i data-lucide="send" width="16" height="16"></i> Enviar solicitação
        </button>
      </div>
    </div>
  </div>

  <!-- PASSO 2B: Serviço -->
  <div id="formServico" style="display:none;">
    <div class="d-flex align-center gap-3 mb-5">
      <button class="btn btn-ghost btn-icon-sm" onclick="voltarCategoria()">
        <i data-lucide="arrow-left" width="16" height="16"></i>
      </button>
      <div>
        <span class="badge badge-servico">Serviço</span>
        <h3 style="font-family:var(--font-primary);font-size:18px;font-weight:var(--fw-bold);margin-top:4px;">Solicitação de Serviço</h3>
      </div>
    </div>

    <div class="card" style="border-radius:var(--radius-xl);">
      <div class="form-group">
        <label class="form-label" style="font-size:15px;">
          Descreva o serviço necessário <span class="required">*</span>
        </label>
        <textarea id="descricaoServico" class="form-control" rows="5"
                  placeholder="Descreva o serviço a ser realizado na escola. Inclua localização, urgência e informações relevantes..."
                  maxlength="300" data-maxlength="300" data-counter="cntServico"></textarea>
        <div class="char-counter" id="cntServico">0/300</div>
      </div>
      <div class="form-group" style="margin-top:var(--space-4);">
        <label class="form-label" style="font-size:15px;">
          Foto do local / problema <span class="required">*</span>
        </label>
        <div id="areaFoto" onclick="document.getElementById('inputFoto').click()"
             style="border:2px dashed var(--neutral-300);border-radius:var(--radius-lg);
                    padding:var(--space-6);text-align:center;cursor:pointer;transition:var(--transition);
                    background:var(--neutral-100);"
             onmouseover="this.style.borderColor='var(--brand)'"
             onmouseout="this.style.borderColor='var(--neutral-300)'">
          <i data-lucide="camera" width="32" height="32" style="color:var(--secondary);margin:0 auto;display:block;"></i>
          <p style="font-size:13px;color:var(--secondary);margin-top:var(--space-2);">Clique para selecionar uma foto</p>
          <p style="font-size:11px;color:var(--neutral-300);margin-top:4px;">JPG ou PNG, maximo 5MB</p>
        </div>
        <input type="file" id="inputFoto" accept="image/jpeg,image/png" style="display:none;" onchange="previewFoto(this)">
        <div id="previewFoto" style="display:none;margin-top:var(--space-3);position:relative;">
          <img id="imgPreview" src="" alt="Preview" style="width:100%;max-height:250px;object-fit:cover;border-radius:var(--radius-lg);">
          <button type="button" onclick="removerFoto()" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.6);border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;color:white;font-size:16px;display:flex;align-items:center;justify-content:center;">&times;</button>
        </div>
      </div>
      <div class="alert alert-info" style="font-size:13px;">
        <i data-lucide="info" width="14" height="14"></i>
        Para serviços diferentes, crie solicitações separadas.
      </div>
      <div class="d-flex justify-between align-center mt-4">
        <div></div>
        <button class="btn btn-primary" id="btnEnviarServico" onclick="enviarSolicitacao('servico')">
          <i data-lucide="send" width="16" height="16"></i> Enviar solicitação
        </button>
      </div>
    </div>
  </div>

</div>

<style>
.card-sel:hover { border-color:var(--primary)!important; box-shadow:var(--shadow-md); transform:translateY(-2px); }
</style>

<script src="/assets/js/img-resize.js"></script>
<script>
const produtos = <?= json_encode(array_values($produtos)) ?>;
const unidades = <?= json_encode(array_values($unidades)) ?>;
let contadorItem = 0;

// Agrupamento global de produtos por categoria
const catLabels = { papelaria: 'Papelaria', higiene: 'Higiene', limpeza: 'Limpeza', outros: 'Outros' };
const catOrder  = ['papelaria', 'higiene', 'limpeza', 'outros'];
const grupos    = {};
catOrder.forEach(c => grupos[c] = []);
produtos.forEach(p => {
  const cat = p.categoria || 'outros';
  if (!grupos[cat]) grupos[cat] = [];
  grupos[cat].push(p);
});
catOrder.forEach(c => grupos[c].sort((a,b) => a.nome.localeCompare(b.nome, 'pt-BR')));
const catOpts = '<option value="">Selecione a categoria...</option>' +
  catOrder.filter(c => grupos[c].length > 0)
  .map(c => `<option value="${c}">${catLabels[c]}</option>`).join('');

function selecionarCategoria(cat) {
  document.getElementById('stepCategoria').style.display = 'none';
  document.getElementById('formAlert').style.display = 'none';
  if (cat === 'produto') {
    document.getElementById('formProduto').style.display = '';
    if (!document.getElementById('listaItens').children.length) adicionarItem();
  } else {
    document.getElementById('formServico').style.display = '';
  }
}

function voltarCategoria() {
  ['formProduto','formServico','formAlert'].forEach(id => document.getElementById(id).style.display = 'none');
  document.getElementById('stepCategoria').style.display = '';
}

function filtrarProdutos(selectCat) {
  const cat      = selectCat.value;
  const grid     = selectCat.closest('[style*="grid"]');
  const selectProd = grid ? grid.querySelector('select.item-produto') : null;
  if (!selectProd) return;

  if (!cat) {
    selectProd.innerHTML = '<option value="">Selecione a categoria primeiro...</option>';
    selectProd.disabled  = true;
    return;
  }

  const prodsFiltrados = (grupos[cat] || []);
  selectProd.innerHTML = '<option value="">Selecione o produto...</option>' +
    prodsFiltrados.map(p => `<option value="${p.id}">${p.nome}</option>`).join('');
  selectProd.disabled = false;
}

function adicionarItem() {
  const lista = document.getElementById('listaItens');
  if (lista.children.length >= 30) { mostrarAlerta('Limite de 30 itens atingido.','warning'); return; }

  const idx       = contadorItem++;
  const num    = lista.children.length + 1;
  const unOpts = unidades.map(u => `<option value="${u.id}">${u.nome} (${u.sigla})</option>`).join('');

  const div = document.createElement('div');
  div.id    = `item-${idx}`;
  div.className = 'animate-slide-up';
  div.style.cssText = 'background:var(--neutral-100);border-radius:var(--radius-lg);padding:var(--space-4);';
  div.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3);">
      <span class="item-num" style="font-size:11px;font-weight:var(--fw-semibold);color:var(--secondary);text-transform:uppercase;letter-spacing:.05em;">Item ${num}</span>
      <button type="button" class="btn btn-ghost btn-icon-sm" onclick="removerItem('item-${idx}')">
        <i data-lucide="trash-2" width="14" height="14" style="color:var(--brand);"></i>
      </button>
    </div>
    <div style="display:grid;grid-template-columns:1fr;gap:var(--space-3);">
      <div>
        <label class="form-label" style="font-size:12px;">Categoria <span class="required">*</span></label>
        <select class="form-control item-categoria" style="background:var(--white);" onchange="filtrarProdutos(this)">
          ${catOpts}
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:12px;">Produto <span class="required">*</span></label>
        <select class="form-control item-produto" style="background:var(--white);" disabled>
          <option value="">Selecione a categoria primeiro...</option>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);">
        <div>
          <label class="form-label" style="font-size:12px;">Quantidade <span class="required">*</span></label>
          <input type="number" min="0.01" step="0.01" class="form-control item-quantidade" placeholder="0" style="background:var(--white);">
        </div>
        <div>
          <label class="form-label" style="font-size:12px;">Unidade <span class="required">*</span></label>
          <select class="form-control item-unidade" style="background:var(--white);">
            <option value="">Selecione...</option>${unOpts}
          </select>
        </div>
      </div>
    </div>
  `;
  lista.appendChild(div);
  if (window.lucide) lucide.createIcons();
}

function removerItem(id) {
  const el = document.getElementById(id);
  if (el) {
    el.style.transition = 'all 0.2s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateX(16px)';
    setTimeout(() => {
      el.remove();
      let i = 1;
      document.querySelectorAll('.item-num').forEach(span => span.textContent = `Item ${i++}`);
    }, 200);
  }
}

function previewFoto(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('imgPreview').src = e.target.result;
    document.getElementById('previewFoto').style.display = '';
    document.getElementById('areaFoto').style.display = 'none';
  };
  reader.readAsDataURL(file);
}

function removerFoto() {
  document.getElementById('inputFoto').value = '';
  document.getElementById('previewFoto').style.display = 'none';
  document.getElementById('areaFoto').style.display = '';
}

async function enviarSolicitacao(categoria) {
  document.getElementById('formAlert').style.display = 'none';
  let payload = { categoria };

  if (categoria === 'servico') {
    const desc = document.getElementById('descricaoServico').value.trim();
    const foto = document.getElementById('inputFoto').files[0];
    if (!desc) return mostrarAlerta('A descrição do serviço é obrigatória.', 'danger');
    if (!foto) return mostrarAlerta('A foto é obrigatória para solicitações de serviço.', 'danger');
    payload.descricao_servico = desc;
    payload._foto = foto;
  } else {
    const itens = [];
    let erro = null;
    document.querySelectorAll('#listaItens > div').forEach((div, i) => {
      if (erro) return;
      const prod = div.querySelector('.item-produto')?.value;
      const qtd  = parseFloat(div.querySelector('.item-quantidade')?.value || '0');
      const un   = div.querySelector('.item-unidade')?.value;
      if (!prod) { erro = `Item ${i+1}: selecione o produto.`; return; }
      if (isNaN(qtd) || qtd <= 0) { erro = `Item ${i+1}: informe uma quantidade válida.`; return; }
      if (!un)  { erro = `Item ${i+1}: selecione a unidade de medida.`; return; }
      itens.push({ id_produto: parseInt(prod), id_unidade: parseInt(un), quantidade: qtd });
    });
    if (erro)  return mostrarAlerta(erro, 'danger');
    if (!itens.length) return mostrarAlerta('Adicione pelo menos um item ao pedido.', 'danger');
    payload.itens = itens;
  }

  const btnId = `btnEnviar${categoria === 'produto' ? 'Produto' : 'Servico'}`;
  const btn   = document.getElementById(btnId);
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span> Enviando...';

  try {
    const res  = await fetch('/api/solicitacoes/index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      // Se for servico com foto, faz upload antes de redirecionar
      let avisoFoto = '';
      if (categoria === 'servico' && payload._foto) {
        const formData = new FormData();
        formData.append('foto', await tdReduzirImagem(payload._foto), 'foto.jpg');
        formData.append('id_solicitacao', data.data.id);
        try {
          const up = await fetch('/api/upload/servico.php', { method: 'POST', body: formData });
          let upData = null;
          try { upData = await up.json(); } catch (_) {}
          if (!up.ok || !upData || !upData.success) {
            avisoFoto = ' ATENÇÃO: a foto NÃO foi enviada'
              + (upData && upData.message ? ' (' + upData.message + ')' : ' (arquivo muito grande ou erro no envio)')
              + '. Avise a Secretaria.';
          }
        } catch (_) {
          avisoFoto = ' ATENÇÃO: a foto NÃO foi enviada (erro de conexão). Avise a Secretaria.';
        }
      }
      mostrarAlerta(data.message + (avisoFoto || ' Redirecionando...'), avisoFoto ? 'warning' : 'success');
      setTimeout(() => window.location.href = `/pages/solicitacao-detalhe.php?id=${data.data.id}`, avisoFoto ? 5000 : 1400);
    } else {
      mostrarAlerta(data.message, 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="send" width="16" height="16"></i> Enviar solicitação';
      if (window.lucide) lucide.createIcons();
    }
  } catch(e) {
    mostrarAlerta('Erro de conexão. Tente novamente.', 'danger');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="send" width="16" height="16"></i> Enviar solicitação';
    if (window.lucide) lucide.createIcons();
  }
}

function mostrarAlerta(msg, tipo) {
  const el = document.getElementById('formAlert');
  el.className = `alert alert-${tipo} animate-slide-up`;
  el.innerHTML = `<i data-lucide="${tipo==='success'?'check-circle':tipo==='warning'?'alert-triangle':'alert-circle'}" width="16" height="16"></i> ${msg}`;
  el.style.display = 'flex';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (window.lucide) lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
