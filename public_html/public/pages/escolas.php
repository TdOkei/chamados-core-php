<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Services\SuperadminService;

AuthMiddleware::requireSuperadmin();

$pageTitle  = 'Escolas';
$activePage = 'escolas';
$service    = new SuperadminService();
$escolas    = $service->listarEscolas();

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <!-- Cabeçalho -->
  <div class="d-flex align-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Escolas</h2>
      <p class="text-body" style="color:var(--secondary); margin-top:4px;">
        <?= count($escolas) ?> escola<?= count($escolas) !== 1 ? 's' : '' ?> cadastrada<?= count($escolas) !== 1 ? 's' : '' ?>.
      </p>
    </div>
    <button class="btn btn-primary" onclick="abrirModal()">
      <i data-lucide="plus" width="16" height="16"></i>
      Nova Escola
    </button>
  </div>

  <!-- Busca -->
  <div class="card-flat mb-4" style="border-radius:var(--radius-lg); padding: var(--space-3) var(--space-4);">
    <div style="position:relative;">
      <i data-lucide="search" width="16" height="16"
         style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--secondary);"></i>
      <input type="text" id="busca" placeholder="Buscar por nome ou código..."
             class="form-control" style="padding-left:40px; background:transparent; border:none; box-shadow:none;"
             oninput="filtrarTabela(this.value)">
    </div>
  </div>

  <!-- Tabela -->
  <div class="table-wrapper animate-slide-up">
    <table class="table" id="tabelaEscolas">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Código</th>
          <th>Diretor(a)</th>
          <th>Endereço</th>
          <th>Telefone</th>
          <th>Status</th>
          <th style="width:120px;">Ações</th>
        </tr>
      </thead>
      <tbody id="tbodyEscolas">
        <?php foreach ($escolas as $e): ?>
        <tr data-search="<?= strtolower(htmlspecialchars($e['nome'] . ' ' . $e['codigo'])) ?>">
          <td>
            <span style="font-weight:var(--fw-semibold);"><?= htmlspecialchars($e['nome']) ?></span>
          </td>
          <td>
            <span class="badge badge-analysis"><?= htmlspecialchars($e['codigo']) ?></span>
          </td>
          <td style="color:var(--secondary); font-size:13px;">
            <?= $e['diretor_nome'] ? htmlspecialchars($e['diretor_nome']) : '<span style="color:var(--neutral-300);">Sem diretor</span>' ?>
          </td>
          <td style="font-size:13px; color:var(--secondary);">
            <?= htmlspecialchars($e['endereco'] ?? '—') ?>
          </td>
          <td style="font-size:13px; color:var(--secondary);">
            <?= htmlspecialchars($e['telefone'] ?? '—') ?>
          </td>
          <td>
            <span class="badge <?= $e['ativo'] ? 'badge-done' : 'badge-cancelled' ?>">
              <?= $e['ativo'] ? 'Ativa' : 'Inativa' ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-ghost btn-icon-sm"
                      title="Editar"
                      onclick='editarEscola(<?= json_encode($e) ?>)'>
                <i data-lucide="pencil" width="14" height="14"></i>
              </button>
              <button class="btn btn-ghost btn-icon-sm"
                      title="<?= $e['ativo'] ? 'Desativar' : 'Ativar' ?>"
                      onclick="toggleEscola(<?= $e['id'] ?>, this)">
                <i data-lucide="<?= $e['ativo'] ? 'eye-off' : 'eye' ?>" width="14" height="14"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($escolas)): ?>
        <tr>
          <td colspan="7">
            <div class="empty-state" style="padding: var(--space-10);">
              <div class="empty-state-icon"><i data-lucide="school" width="28" height="28"></i></div>
              <p class="empty-state-text">Nenhuma escola cadastrada ainda.</p>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ====== MODAL ESCOLA ====== -->
<div class="modal-overlay" id="modalOverlay" style="display:none;" onclick="fecharModalFora(event)">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitulo">Nova Escola</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal()">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <div id="modalAlert" style="display:none;" class="mb-4"></div>
      <form id="formEscola" novalidate>
        <input type="hidden" id="escolaId">

        <div class="grid-2" style="gap:var(--space-4);">
          <div class="form-group" style="grid-column:1/-1;">
            <label class="form-label">Nome <span class="required">*</span></label>
            <input type="text" id="escolaNome" class="form-control"
                   placeholder="Ex: Escola Municipal João da Silva" maxlength="150">
            <div class="form-error" id="errNome"></div>
          </div>

          <div class="form-group">
            <label class="form-label">Código <span class="required">*</span></label>
            <input type="text" id="escolaCodigo" class="form-control"
                   placeholder="Ex: EM001" maxlength="30"
                   style="text-transform:uppercase;">
            <div class="form-hint">Identificador único da escola.</div>
            <div class="form-error" id="errCodigo"></div>
          </div>

          <div class="form-group">
            <label class="form-label">Telefone</label>
            <input type="text" id="escolaTelefone" class="form-control"
                   placeholder="(00) 00000-0000" maxlength="20">
          </div>

          <div class="form-group" style="grid-column:1/-1;">
            <label class="form-label">Endereço</label>
            <input type="text" id="escolaEndereco" class="form-control"
                   placeholder="Rua, número, bairro" maxlength="255">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharModal()">Cancelar</button>
      <button class="btn btn-primary" id="btnSalvarEscola" onclick="salvarEscola()">
        Salvar
      </button>
    </div>
  </div>
</div>

<script>
// ------ Filtro de busca ------
function filtrarTabela(termo) {
  const t = termo.toLowerCase();
  document.querySelectorAll('#tbodyEscolas tr[data-search]').forEach(tr => {
    tr.style.display = tr.dataset.search.includes(t) ? '' : 'none';
  });
}

// ------ Modal ------
function abrirModal() {
  document.getElementById('escolaId').value      = '';
  document.getElementById('escolaNome').value    = '';
  document.getElementById('escolaCodigo').value  = '';
  document.getElementById('escolaEndereco').value= '';
  document.getElementById('escolaTelefone').value= '';
  document.getElementById('modalTitulo').textContent = 'Nova Escola';
  document.getElementById('modalAlert').style.display = 'none';
  ['errNome','errCodigo'].forEach(id => document.getElementById(id).textContent = '');
  document.getElementById('modalOverlay').style.display = 'flex';
}

function editarEscola(e) {
  document.getElementById('escolaId').value       = e.id;
  document.getElementById('escolaNome').value     = e.nome;
  document.getElementById('escolaCodigo').value   = e.codigo;
  document.getElementById('escolaEndereco').value = e.endereco || '';
  document.getElementById('escolaTelefone').value = e.telefone || '';
  document.getElementById('modalTitulo').textContent = 'Editar Escola';
  document.getElementById('modalAlert').style.display = 'none';
  ['errNome','errCodigo'].forEach(id => document.getElementById(id).textContent = '');
  document.getElementById('modalOverlay').style.display = 'flex';
}

function fecharModal() {
  document.getElementById('modalOverlay').style.display = 'none';
}

function fecharModalFora(e) {
  if (e.target === document.getElementById('modalOverlay')) fecharModal();
}

// ------ Salvar ------
async function salvarEscola() {
  const id       = document.getElementById('escolaId').value;
  const nome     = document.getElementById('escolaNome').value.trim();
  const codigo   = document.getElementById('escolaCodigo').value.trim().toUpperCase();
  const endereco = document.getElementById('escolaEndereco').value.trim();
  const telefone = document.getElementById('escolaTelefone').value.trim();

  ['errNome','errCodigo'].forEach(i => document.getElementById(i).textContent = '');
  let ok = true;
  if (!nome)   { document.getElementById('errNome').textContent   = 'Obrigatório.'; ok = false; }
  if (!codigo) { document.getElementById('errCodigo').textContent = 'Obrigatório.'; ok = false; }
  if (!ok) return;

  const btn = document.getElementById('btnSalvarEscola');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';

  try {
    const method = id ? 'PUT' : 'POST';
    const url    = id
      ? `/api/escolas/index.php?id=${id}`
      : '/api/escolas/index.php';

    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nome, codigo, endereco, telefone }),
    });
    const data = await res.json();

    if (data.success) {
      fecharModal();
      showToast(data.message, 'success');
      setTimeout(() => location.reload(), 900);
    } else {
      mostrarAlerta(data.message, 'danger');
    }
  } catch (e) {
    mostrarAlerta('Erro de conexão. Tente novamente.', 'danger');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar';
  }
}

// ------ Toggle ativo ------
async function toggleEscola(id, btn) {
  if (!confirm('Confirma alterar o status desta escola?')) return;
  try {
    const res  = await fetch(`/api/escolas/index.php?id=${id}&acao=toggle`, { method: 'POST' });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 900); }
    else showToast(data.message, 'danger');
  } catch (e) { showToast('Erro de conexão.', 'danger'); }
}

// ------ Helpers ------
function mostrarAlerta(msg, tipo) {
  const el = document.getElementById('modalAlert');
  el.className = `alert alert-${tipo}`;
  el.innerHTML = `<i data-lucide="${tipo === 'success' ? 'check-circle' : 'alert-circle'}" width="16" height="16"></i> ${msg}`;
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

// ------ Uppercase automático no código ------
document.getElementById('escolaCodigo')?.addEventListener('input', function() {
  this.value = this.value.toUpperCase();
});
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
