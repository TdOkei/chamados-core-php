<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Services\SuperadminService;

AuthMiddleware::requireSuperadmin();

$pageTitle  = 'Usuários';
$activePage = 'usuarios';
$service    = new SuperadminService();
$usuarios   = $service->listarUsuarios();
$escolas    = $service->listarEscolas(apenasAtivas: true);

$perfisLabel = [
    'administrador' => 'Administrador',
    'diretor'       => 'Diretor(a)',
    'secretario'    => 'Secretario(a)',
    'escriturario'  => 'Escriturário(a)',
    'estagiario'    => 'Estagiário(a)',
];
$catLabel = ['produto' => 'Produtos', 'servico' => 'Serviços'];

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <!-- Cabeçalho -->
  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Usuários</h2>
      <p class="text-body" style="color:var(--secondary); margin-top:4px;">
        <?= count($usuarios) ?> usuário<?= count($usuarios) !== 1 ? 's' : '' ?> cadastrado<?= count($usuarios) !== 1 ? 's' : '' ?>.
      </p>
    </div>
    <button class="btn btn-primary" onclick="abrirModal()">
      <i data-lucide="plus" width="16" height="16"></i>
      Novo Usuário
    </button>
  </div>

  <!-- Filtros -->
  <div class="d-flex gap-3 mb-4 flex-wrap">
    <div style="position:relative; flex:1; min-width:200px;">
      <i data-lucide="search" width="16" height="16"
         style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--secondary);pointer-events:none;"></i>
      <input type="text" placeholder="Buscar por nome ou usuário..."
             class="form-control" style="padding-left:40px;"
             oninput="filtrar()">
    </div>
    <select class="form-control" id="filtroPerfil" style="width:auto;" onchange="filtrar()">
      <option value="">Todos os perfis</option>
      <option value="administrador">Administrador</option>
      <option value="diretor">Diretor(a)</option>
      <option value="escriturario">Escriturário(a)</option>
      <option value="estagiario">Estagiário(a)</option>
    </select>
    <select class="form-control" id="filtroStatus" style="width:auto;" onchange="filtrar()">
      <option value="">Todos os status</option>
      <option value="ativo">Ativos</option>
      <option value="inativo">Inativos</option>
      <option value="bloqueado">Bloqueados</option>
    </select>
  </div>

  <!-- Tabela -->
  <div class="table-wrapper animate-slide-up">
    <table class="table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Usuário / E-mail</th>
          <th>Perfil</th>
          <th>Escola / Categoria</th>
          <th>Status</th>
          <th style="width:120px;">Ações</th>
        </tr>
      </thead>
      <tbody id="tbodyUsuarios">
        <?php foreach ($usuarios as $u):
          $isBlock  = (bool) $u['bloqueado'];
          $isActive = (bool) $u['ativo'];
        ?>
        <tr data-nome="<?= strtolower(htmlspecialchars($u['nome'] . ' ' . $u['username'])) ?>"
            data-perfil="<?= $u['perfil'] ?>"
            data-status="<?= $isBlock ? 'bloqueado' : ($isActive ? 'ativo' : 'inativo') ?>">
          <td>
            <div style="display:flex;align-items:center;gap:var(--space-3);">
              <?php
              $ini = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', trim($u['nome'])), 0, 2)));
              ?>
              <div class="user-avatar" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
                <?= htmlspecialchars($ini) ?>
              </div>
              <div>
                <div style="font-weight:var(--fw-semibold);font-size:14px;"><?= htmlspecialchars($u['nome']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <div style="font-size:13px;">@<?= htmlspecialchars($u['username']) ?></div>
            <div style="font-size:12px;color:var(--secondary);"><?= htmlspecialchars($u['email']) ?></div>
          </td>
          <td>
            <span class="badge <?= ['administrador' => 'badge-analysis', 'escriturario' => 'badge-perfil-escriturario', 'estagiario' => 'badge-perfil-estagiario'][$u['perfil']] ?? 'badge-perfil-diretor' ?>">
              <?= $perfisLabel[$u['perfil']] ?? $u['perfil'] ?>
            </span>
          </td>
          <td style="font-size:13px;color:var(--secondary);">
            <?php if (in_array($u['perfil'], ['diretor','escriturario','estagiario'], true)): ?>
              <?= htmlspecialchars($u['escola_nome'] ?? '—') ?>
            <?php elseif ($u['perfil'] === 'administrador' && $u['categoria']): ?>
              <span class="badge badge-<?= $u['categoria'] ?>"><?= $catLabel[$u['categoria']] ?></span>
            <?php else: ?>—
            <?php endif; ?>
          </td>
          <td>
            <?php if ($isBlock): ?>
              <span class="badge badge-rejected">Bloqueado</span>
            <?php elseif ($isActive): ?>
              <span class="badge badge-done">Ativo</span>
            <?php else: ?>
              <span class="badge badge-cancelled">Inativo</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-ghost btn-icon-sm" title="Editar"
                      onclick='editarUsuario(<?= json_encode($u) ?>)'>
                <i data-lucide="pencil" width="14" height="14"></i>
              </button>
              <?php if ($isBlock): ?>
              <button class="btn btn-ghost btn-icon-sm" title="Redefinir senha e desbloquear"
                      onclick="abrirRedefinirSenha(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nome']) ?>')">
                <i data-lucide="unlock" width="14" height="14"></i>
              </button>
              <?php else: ?>
              <button class="btn btn-ghost btn-icon-sm" title="Redefinir senha"
                      onclick="abrirRedefinirSenha(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nome']) ?>')">
                <i data-lucide="key" width="14" height="14"></i>
              </button>
              <?php endif; ?>
              <button class="btn btn-ghost btn-icon-sm" title="<?= $isActive ? 'Desativar' : 'Ativar' ?>"
                      onclick="toggleUsuario(<?= $u['id'] ?>, this)">
                <i data-lucide="<?= $isActive ? 'eye-off' : 'eye' ?>" width="14" height="14"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
        <tr><td colspan="6">
          <div class="empty-state" style="padding:var(--space-10);">
            <div class="empty-state-icon"><i data-lucide="users" width="28" height="28"></i></div>
            <p class="empty-state-text">Nenhum usuário cadastrado.</p>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ====== MODAL USUÁRIO ====== -->
<div class="modal-overlay" id="modalUsuario" style="display:none;" onclick="fecharModalFora(event,'modalUsuario')">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h3 class="modal-title" id="tituloModal">Novo Usuário</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal('modalUsuario')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <div id="alertUsuario" style="display:none;" class="mb-4"></div>
      <input type="hidden" id="usuarioId">

      <div class="grid-2" style="gap:var(--space-4);">
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Nome completo <span class="required">*</span></label>
          <input type="text" id="uNome" class="form-control" placeholder="Nome completo" maxlength="100">
        </div>
        <div class="form-group">
          <label class="form-label">Nome de usuário <span class="required">*</span></label>
          <input type="text" id="uUsername" class="form-control" placeholder="usuario.nome" maxlength="60">
        </div>
        <div class="form-group">
          <label class="form-label">E-mail <span class="required">*</span></label>
          <input type="email" id="uEmail" class="form-control" placeholder="email@escola.edu.br" maxlength="150">
        </div>
        <div class="form-group">
          <label class="form-label">Perfil <span class="required">*</span></label>
          <select id="uPerfil" class="form-control" onchange="toggleCamposPerfil()">
            <option value="">Selecione...</option>
            <option value="administrador">Administrador</option>
            <option value="diretor">Diretor(a)</option>
            <option value="escriturario">Escriturário(a)</option>
            <option value="estagiario">Estagiário(a)</option>
            <option value="secretario">Secretario(a)</option>
          </select>
        </div>
        <div class="form-group" id="campoCategoria" style="display:none;">
          <label class="form-label">Categoria <span class="required">*</span></label>
          <select id="uCategoria" class="form-control">
            <option value="">Selecione...</option>
            <option value="produto">Produtos</option>
            <option value="servico">Serviços</option>
          </select>
        </div>
        <div class="form-group" id="campoEscola" style="display:none;">
          <label class="form-label">Escola <span class="required">*</span></label>
          <select id="uEscola" class="form-control">
            <option value="">Selecione...</option>
            <?php foreach ($escolas as $esc): ?>
            <option value="<?= $esc['id'] ?>"><?= htmlspecialchars($esc['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" id="campaSenha" style="grid-column:1/-1;">
          <label class="form-label">Senha <span class="required">*</span> <span id="senhaHint" style="font-weight:normal;color:var(--secondary);font-size:12px;">(obrigatória no cadastro)</span></label>
          <input type="password" id="uSenha" class="form-control" placeholder="Mínimo 8 caracteres" maxlength="100">
          <div class="form-hint">Mínimo 8 caracteres, 1 maiúscula e 1 número.</div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharModal('modalUsuario')">Cancelar</button>
      <button class="btn btn-primary" id="btnSalvarUsuario" onclick="salvarUsuario()">Salvar</button>
    </div>
  </div>
</div>

<!-- ====== MODAL REDEFINIR SENHA ====== -->
<div class="modal-overlay" id="modalSenha" style="display:none;" onclick="fecharModalFora(event,'modalSenha')">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h3 class="modal-title">Redefinir Senha</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharModal('modalSenha')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <p class="text-body mb-4" style="color:var(--secondary);">
        Redefinindo senha de: <strong id="nomeRedefinir"></strong>
      </p>
      <div id="alertSenha" style="display:none;" class="mb-4"></div>
      <input type="hidden" id="idRedefinir">
      <div class="form-group">
        <label class="form-label">Nova senha <span class="required">*</span></label>
        <input type="password" id="novaSenhaAdmin" class="form-control" placeholder="Mínimo 8 caracteres" maxlength="100">
        <div class="form-hint">Mínimo 8 caracteres, 1 maiúscula e 1 número.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirmar senha <span class="required">*</span></label>
        <input type="password" id="confirmarSenhaAdmin" class="form-control" placeholder="Repita a senha" maxlength="100">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharModal('modalSenha')">Cancelar</button>
      <button class="btn btn-brand" id="btnRedefinir" onclick="redefinirSenha()">Confirmar</button>
    </div>
  </div>
</div>

<script>
// ---- Filtros ----
function filtrar() {
  const busca  = document.querySelector('input[placeholder*="Buscar"]').value.toLowerCase();
  const perfil = document.getElementById('filtroPerfil').value;
  const status = document.getElementById('filtroStatus').value;

  document.querySelectorAll('#tbodyUsuarios tr[data-nome]').forEach(tr => {
    const ok = (!busca  || tr.dataset.nome.includes(busca))
            && (!perfil || tr.dataset.perfil === perfil)
            && (!status || tr.dataset.status === status);
    tr.style.display = ok ? '' : 'none';
  });
}

// ---- Modal Usuário ----
let modoEdicao = false;

function abrirModal() {
  modoEdicao = false;
  ['uNome','uUsername','uEmail','uSenha'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('uPerfil').value   = '';
  document.getElementById('uCategoria').value= '';
  document.getElementById('uEscola').value   = '';
  document.getElementById('usuarioId').value = '';
  document.getElementById('tituloModal').textContent = 'Novo Usuário';
  document.getElementById('campaSenha').style.display = '';
  document.getElementById('senhaHint').textContent = '(obrigatória no cadastro)';
  document.getElementById('uSenha').required = true;
  document.getElementById('alertUsuario').style.display = 'none';
  toggleCamposPerfil();
  abrirModalEl('modalUsuario');
}

function editarUsuario(u) {
  modoEdicao = true;
  const inativo = !u.ativo || u.ativo == 0;

  document.getElementById('usuarioId').value = u.id;
  document.getElementById('uNome').value     = u.nome;
  document.getElementById('uUsername').value = u.username;
  document.getElementById('uEmail').value    = u.email;
  document.getElementById('uSenha').value    = '';
  document.getElementById('tituloModal').textContent = 'Editar Usuário';
  document.getElementById('campaSenha').style.display = 'none';
  document.getElementById('alertUsuario').style.display = 'none';

  const selPerfil = document.getElementById('uPerfil');
  const selEscola = document.getElementById('uEscola');

  if (inativo) {
    // Usuario inativo: mostrar "Nao se aplica" e desabilitar campos
    selPerfil.innerHTML = `<option value="${u.perfil}">Não se aplica</option>`;
    selPerfil.disabled  = true;
    selEscola.innerHTML = `<option value="${u.escola_id || ''}">Não se aplica</option>`;
    selEscola.disabled  = true;
    document.getElementById('campoCategoria').style.display = 'none';
    document.getElementById('campoEscola').style.display    = '';
  } else {
    // Usuario ativo: restaurar selects normais
    selPerfil.disabled = false;
    selEscola.disabled = false;
    selPerfil.value    = u.perfil;
    document.getElementById('uCategoria').value = u.categoria || '';
    selEscola.value    = u.escola_id || '';
    toggleCamposPerfil();
  }

  abrirModalEl('modalUsuario');
}

function toggleCamposPerfil() {
  const p = document.getElementById('uPerfil').value;
  document.getElementById('campoCategoria').style.display = p === 'administrador' ? '' : 'none';
  document.getElementById('campoEscola').style.display    = (p === 'diretor' || p === 'escriturario' || p === 'estagiario') ? '' : 'none';
}

async function salvarUsuario() {
  const id       = document.getElementById('usuarioId').value;
  const nome     = document.getElementById('uNome').value.trim();
  const username = document.getElementById('uUsername').value.trim();
  const email    = document.getElementById('uEmail').value.trim();
  const perfil   = document.getElementById('uPerfil').value;
  const categoria= document.getElementById('uCategoria').value;
  const escola   = document.getElementById('uEscola').value;
  const senha    = document.getElementById('uSenha').value;

  document.getElementById('alertUsuario').style.display = 'none';

  if (!nome || !username || !email || !perfil) {
    return mostrarAlert('alertUsuario', 'Preencha todos os campos obrigatórios.', 'danger');
  }
  if (!modoEdicao && !senha) {
    return mostrarAlert('alertUsuario', 'A senha é obrigatória no cadastro.', 'danger');
  }

  const btn = document.getElementById('btnSalvarUsuario');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';

  const payload = { nome, username, email, perfil, categoria, id_escola: escola };
  if (!modoEdicao) payload.senha = senha;

  try {
    const method = modoEdicao ? 'PUT' : 'POST';
    const url    = modoEdicao
      ? `/api/usuarios/index.php?id=${id}`
      : '/api/usuarios/index.php';

    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      fecharModal('modalUsuario');
      showToast(data.message, 'success');
      setTimeout(() => location.reload(), 900);
    } else {
      mostrarAlert('alertUsuario', data.message, 'danger');
    }
  } catch (e) {
    mostrarAlert('alertUsuario', 'Erro de conexão.', 'danger');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar';
  }
}

// ---- Toggle ativo ----
async function toggleUsuario(id, btn) {
  if (!confirm('Confirma alterar o status deste usuário?')) return;
  try {
    const res  = await fetch(`/api/usuarios/index.php?id=${id}&acao=toggle`, { method: 'POST' });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 900); }
    else showToast(data.message, 'danger');
  } catch (e) { showToast('Erro de conexão.', 'danger'); }
}

// ---- Redefinir senha ----
function abrirRedefinirSenha(id, nome) {
  document.getElementById('idRedefinir').value = id;
  document.getElementById('nomeRedefinir').textContent = nome;
  document.getElementById('novaSenhaAdmin').value      = '';
  document.getElementById('confirmarSenhaAdmin').value = '';
  document.getElementById('alertSenha').style.display  = 'none';
  abrirModalEl('modalSenha');
}

async function redefinirSenha() {
  const id          = document.getElementById('idRedefinir').value;
  const novaSenha   = document.getElementById('novaSenhaAdmin').value;
  const confirmacao = document.getElementById('confirmarSenhaAdmin').value;

  if (!novaSenha || !confirmacao) {
    return mostrarAlert('alertSenha', 'Preencha os dois campos.', 'danger');
  }

  const btn = document.getElementById('btnRedefinir');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';

  try {
    const res  = await fetch('/api/auth/redefinir-senha-admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_usuario: parseInt(id), nova_senha: novaSenha, confirmacao }),
    });
    const data = await res.json();
    if (data.success) {
      fecharModal('modalSenha');
      showToast(data.message, 'success');
      setTimeout(() => location.reload(), 900);
    } else {
      mostrarAlert('alertSenha', data.message, 'danger');
    }
  } catch (e) {
    mostrarAlert('alertSenha', 'Erro de conexão.', 'danger');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Confirmar';
  }
}

// ---- Utilitários ----
function abrirModalEl(id)  { document.getElementById(id).style.display = 'flex'; }
function fecharModal(id)   { document.getElementById(id).style.display = 'none'; }
function fecharModalFora(e, id) { if (e.target.id === id) fecharModal(id); }

function mostrarAlert(targetId, msg, tipo) {
  const el = document.getElementById(targetId);
  el.className = `alert alert-${tipo} animate-slide-up`;
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
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
