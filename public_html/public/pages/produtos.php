<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Services\SuperadminService;

AuthMiddleware::requireSuperadmin();

$pageTitle  = 'Produtos';
$activePage = 'produtos';
$service    = new SuperadminService();
$produtos   = $service->listarProdutos();

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="d-flex align-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Produtos</h2>
      <p class="text-body" style="color:var(--secondary); margin-top:4px;">
        <?= count($produtos) ?> produto<?= count($produtos) !== 1 ? 's' : '' ?> cadastrado<?= count($produtos) !== 1 ? 's' : '' ?>.
      </p>
    </div>
    <button class="btn btn-primary" onclick="abrirModal()">
      <i data-lucide="plus" width="16" height="16"></i>
      Novo Produto
    </button>
  </div>

  <!-- Busca -->
  <div class="card-flat mb-4" style="border-radius:var(--radius-lg); padding:var(--space-3) var(--space-4);">
    <div style="display:flex;align-items:center;gap:var(--space-3);">
      <div style="position:relative;flex:1;">
        <i data-lucide="search" width="16" height="16"
           style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--secondary);pointer-events:none;"></i>
        <input type="text" id="campoBusca" placeholder="Buscar produto..." class="form-control"
               style="padding-left:40px; border:none; background:transparent; box-shadow:none;"
               oninput="filtrar()">
      </div>
      <select id="filtroCategoria" class="form-control" style="width:auto;" onchange="filtrar()">
        <option value="">Todas as categorias</option>
        <option value="papelaria">Papelaria</option>
        <option value="higiene">Higiene</option>
        <option value="limpeza">Limpeza</option>
        <option value="outros">Outros</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper animate-slide-up">
    <table class="table">
      <thead>
        <tr>
          <th>Nome do Produto</th>
          <th>Categoria</th>
          <th>Descrição</th>
          <th>Status</th>
          <th>Cadastrado em</th>
          <th style="width:100px;">Ações</th>
        </tr>
      </thead>
      <tbody id="tbodyProdutos">
        <?php foreach ($produtos as $p): ?>
        <tr data-search="<?= strtolower(htmlspecialchars($p['nome'])) ?>">
          <td style="font-weight:var(--fw-semibold);"><?= htmlspecialchars($p['nome']) ?></td>
          <td>
            <?php
              $catLabel = ['papelaria'=>'Papelaria','higiene'=>'Higiene','limpeza'=>'Limpeza','outros'=>'Outros'];
              $catClass = ['papelaria'=>'badge-analysis','higiene'=>'badge-processing','limpeza'=>'badge-dispatched','outros'=>'badge-pending'];
              $cat = $p['categoria'] ?? 'outros';
            ?>
            <span class="badge <?= $catClass[$cat] ?>"><?= $catLabel[$cat] ?></span>
          </td>
          <td style="font-size:13px;color:var(--secondary);max-width:300px;">
            <?= htmlspecialchars($p['descricao'] ?? '—') ?>
          </td>
          <td>
            <span class="badge <?= $p['ativo'] ? 'badge-done' : 'badge-cancelled' ?>">
              <?= $p['ativo'] ? 'Ativo' : 'Inativo' ?>
            </span>
          </td>
          <td style="font-size:13px;color:var(--secondary);">
            <?= date('d/m/Y', strtotime($p['created_at'])) ?>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-ghost btn-icon-sm" title="Editar"
                      onclick='editarProduto(<?= json_encode($p) ?>)'>
                <i data-lucide="pencil" width="14" height="14"></i>
              </button>
              <button class="btn btn-ghost btn-icon-sm" title="<?= $p['ativo'] ? 'Desativar' : 'Ativar' ?>"
                      onclick="toggleProduto(<?= $p['id'] ?>)">
                <i data-lucide="<?= $p['ativo'] ? 'eye-off' : 'eye' ?>" width="14" height="14"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($produtos)): ?>
        <tr><td colspan="5">
          <div class="empty-state" style="padding:var(--space-10);">
            <div class="empty-state-icon"><i data-lucide="package" width="28" height="28"></i></div>
            <p class="empty-state-text">Nenhum produto cadastrado ainda.</p>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" style="display:none;" onclick="if(event.target===this)fecharModal()">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitulo">Novo Produto</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal()">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <div id="modalAlert" style="display:none;" class="mb-4"></div>
      <input type="hidden" id="produtoId">

      <div class="form-group">
        <label class="form-label">Nome <span class="required">*</span></label>
        <input type="text" id="pNome" class="form-control" placeholder="Nome do produto" maxlength="150">
        </div>
        <div class="form-group">
          <label class="form-label">Categoria <span class="required">*</span></label>
          <select id="pCategoria" class="form-control">
            <option value="papelaria">Papelaria</option>
            <option value="higiene">Higiene</option>
            <option value="limpeza">Limpeza</option>
            <option value="outros">Outros</option>
          </select>
      </div>
      <div class="form-group">
        <label class="form-label">Descrição</label>
        <textarea id="pDesc" class="form-control" rows="3" maxlength="400"
                  placeholder="Descrição opcional do produto..."
                  data-maxlength="400" data-counter="cntDesc"></textarea>
        <div class="char-counter" id="cntDesc">0/400</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharModal()">Cancelar</button>
      <button class="btn btn-primary" id="btnSalvar" onclick="salvarProduto()">Salvar</button>
    </div>
  </div>
</div>

<script>
function filtrar() {
  const t   = document.getElementById('campoBusca').value.toLowerCase();
  const cat = document.getElementById('filtroCategoria').value;
  document.querySelectorAll('#tbodyProdutos tr[data-search]').forEach(tr => {
    const matchNome = tr.dataset.search.includes(t);
    const matchCat  = !cat || tr.dataset.categoria === cat;
    tr.style.display = matchNome && matchCat ? '' : 'none';
  });
}

function abrirModal() {
  document.getElementById('produtoId').value = '';
  document.getElementById('pNome').value      = '';
  document.getElementById('pCategoria').value = 'outros';
  document.getElementById('pDesc').value = '';
  document.getElementById('modalTitulo').textContent = 'Novo Produto';
  document.getElementById('modalAlert').style.display = 'none';
  document.getElementById('modalOverlay').style.display = 'flex';
  // Trigger char counter
  document.getElementById('pDesc').dispatchEvent(new Event('input'));
}

function editarProduto(p) {
  document.getElementById('produtoId').value = p.id;
  document.getElementById('pNome').value      = p.nome;
  document.getElementById('pCategoria').value = p.categoria || 'outros';
  document.getElementById('pDesc').value     = p.descricao || '';
  document.getElementById('modalTitulo').textContent = 'Editar Produto';
  document.getElementById('modalAlert').style.display = 'none';
  document.getElementById('modalOverlay').style.display = 'flex';
  document.getElementById('pDesc').dispatchEvent(new Event('input'));
}

function fecharModal() { document.getElementById('modalOverlay').style.display = 'none'; }

async function salvarProduto() {
  const id   = document.getElementById('produtoId').value;
  const nome      = document.getElementById('pNome').value.trim();
  const categoria = document.getElementById('pCategoria').value;
  const desc = document.getElementById('pDesc').value.trim();

  if (!nome) {
    mostrarAlert('O nome do produto é obrigatório.', 'danger');
    return;
  }

  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';

  try {
    const method = id ? 'PUT' : 'POST';
    const url    = id ? `/api/produtos/index.php?id=${id}` : '/api/produtos/index.php';
    const res    = await fetch(url, {
      method, headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nome, categoria, descricao: desc }),
    });
    const data = await res.json();

    if (data.success) {
      fecharModal();
      showToast(data.message, 'success');
      setTimeout(() => location.reload(), 900);
    } else { mostrarAlert(data.message, 'danger'); }
  } catch (e) { mostrarAlert('Erro de conexão.', 'danger'); }
  finally { btn.disabled = false; btn.textContent = 'Salvar'; }
}

async function toggleProduto(id) {
  if (!confirm('Confirma alterar o status deste produto?')) return;
  const res  = await fetch(`/api/produtos/index.php?id=${id}&acao=toggle`, { method: 'POST' });
  const data = await res.json();
  if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 900); }
  else showToast(data.message, 'danger');
}

function mostrarAlert(msg, tipo) {
  const el = document.getElementById('modalAlert');
  el.className = `alert alert-${tipo}`;
  el.innerHTML = `<i data-lucide="alert-circle" width="16" height="16"></i> ${msg}`;
  el.style.display = 'flex';
  if (window.lucide) lucide.createIcons();
}

function showToast(msg, tipo) {
  const t = document.createElement('div');
  t.className = `alert alert-${tipo} animate-slide-up`;
  t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;max-width:340px;box-shadow:var(--shadow-xl);';
  t.innerHTML = `<i data-lucide="${tipo === 'success' ? 'check-circle' : 'alert-circle'}" width="16" height="16"></i> ${msg}`;
  document.body.appendChild(t);
  if (window.lucide) lucide.createIcons();
  setTimeout(() => t.remove(), 3500);
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
