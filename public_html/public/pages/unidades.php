<?php
// public/pages/unidades.php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Services\SuperadminService;

AuthMiddleware::requireSuperadmin();

$pageTitle  = 'Unidades de Medida';
$activePage = 'unidades';
$service    = new SuperadminService();
$unidades   = $service->listarUnidades();

require_once __DIR__ . '/../../templates/layout-header.php';
?>
<div class="animate-fade-in">
  <div class="d-flex align-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Unidades de Medida</h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;"><?= count($unidades) ?> unidade<?= count($unidades) !== 1 ? 's' : '' ?> cadastrada<?= count($unidades) !== 1 ? 's' : '' ?>.</p>
    </div>
    <button class="btn btn-primary" onclick="abrirModal()">
      <i data-lucide="plus" width="16" height="16"></i> Nova Unidade
    </button>
  </div>

  <div class="table-wrapper animate-slide-up">
    <table class="table">
      <thead><tr><th>Nome</th><th>Sigla</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($unidades as $u): ?>
        <tr>
          <td style="font-weight:var(--fw-semibold);"><?= htmlspecialchars($u['nome']) ?></td>
          <td><span class="badge badge-analysis"><?= htmlspecialchars($u['sigla']) ?></span></td>
          <td><span class="badge <?= $u['ativo'] ? 'badge-done' : 'badge-cancelled' ?>"><?= $u['ativo'] ? 'Ativa' : 'Inativa' ?></span></td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-ghost btn-icon-sm" onclick='editarUnidade(<?= json_encode($u) ?>)'>
                <i data-lucide="pencil" width="14" height="14"></i>
              </button>
              <button class="btn btn-ghost btn-icon-sm" onclick="toggleUnidade(<?= $u['id'] ?>)">
                <i data-lucide="<?= $u['ativo'] ? 'eye-off' : 'eye' ?>" width="14" height="14"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($unidades)): ?>
        <tr><td colspan="4"><div class="empty-state" style="padding:var(--space-10);"><div class="empty-state-icon"><i data-lucide="ruler" width="28" height="28"></i></div><p class="empty-state-text">Nenhuma unidade cadastrada.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modal" style="display:none;" onclick="if(event.target===this)fechar()">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h3 class="modal-title" id="mTitulo">Nova Unidade</h3>
      <button class="btn btn-ghost btn-icon" onclick="fechar()"><i data-lucide="x" width="18" height="18"></i></button>
    </div>
    <div class="modal-body">
      <div id="mAlert" style="display:none;" class="mb-4"></div>
      <input type="hidden" id="uId">
      <div class="form-group">
        <label class="form-label">Nome <span class="required">*</span></label>
        <input type="text" id="uNome" class="form-control" placeholder="Ex: Quilograma" maxlength="50">
      </div>
      <div class="form-group">
        <label class="form-label">Sigla <span class="required">*</span></label>
        <input type="text" id="uSigla" class="form-control" placeholder="Ex: kg" maxlength="10" style="text-transform:lowercase;">
        <div class="form-hint">Máximo 10 caracteres. Será convertida para minúsculas.</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fechar()">Cancelar</button>
      <button class="btn btn-primary" id="btnSalvar" onclick="salvar()">Salvar</button>
    </div>
  </div>
</div>

<script>
function abrirModal() {
  document.getElementById('uId').value = '';
  document.getElementById('uNome').value = '';
  document.getElementById('uSigla').value = '';
  document.getElementById('mTitulo').textContent = 'Nova Unidade';
  document.getElementById('mAlert').style.display = 'none';
  document.getElementById('modal').style.display = 'flex';
}
function editarUnidade(u) {
  document.getElementById('uId').value = u.id;
  document.getElementById('uNome').value = u.nome;
  document.getElementById('uSigla').value = u.sigla;
  document.getElementById('mTitulo').textContent = 'Editar Unidade';
  document.getElementById('mAlert').style.display = 'none';
  document.getElementById('modal').style.display = 'flex';
}
function fechar() { document.getElementById('modal').style.display = 'none'; }

async function salvar() {
  const id   = document.getElementById('uId').value;
  const nome = document.getElementById('uNome').value.trim();
  const sigla= document.getElementById('uSigla').value.trim().toLowerCase();
  if (!nome || !sigla) return alerta('Preencha nome e sigla.', 'danger');

  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';

  try {
    const res  = await fetch(id ? `/api/unidades/index.php?id=${id}` : '/api/unidades/index.php', {
      method: id ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nome, sigla }),
    });
    const data = await res.json();
    if (data.success) { fechar(); toast(data.message,'success'); setTimeout(()=>location.reload(),900); }
    else alerta(data.message,'danger');
  } finally { btn.disabled=false; btn.textContent='Salvar'; }
}

async function toggleUnidade(id) {
  if (!confirm('Confirma alterar o status?')) return;
  const res  = await fetch(`/api/unidades/index.php?id=${id}&acao=toggle`,{method:'POST'});
  const data = await res.json();
  if (data.success) { toast(data.message,'success'); setTimeout(()=>location.reload(),900); }
  else toast(data.message,'danger');
}

function alerta(msg, tipo) {
  const el = document.getElementById('mAlert');
  el.className = `alert alert-${tipo}`;
  el.innerHTML = `<i data-lucide="alert-circle" width="16" height="16"></i> ${msg}`;
  el.style.display = 'flex';
  if(window.lucide) lucide.createIcons();
}
function toast(msg, tipo) {
  const t = document.createElement('div');
  t.className = `alert alert-${tipo} animate-slide-up`;
  t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;max-width:340px;box-shadow:var(--shadow-xl);';
  t.innerHTML = `<i data-lucide="${tipo==='success'?'check-circle':'alert-circle'}" width="16" height="16"></i> ${msg}`;
  document.body.appendChild(t);
  if(window.lucide) lucide.createIcons();
  setTimeout(()=>t.remove(),3500);
}
document.getElementById('uSigla')?.addEventListener('input',function(){this.value=this.value.toLowerCase();});
</script>
<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
