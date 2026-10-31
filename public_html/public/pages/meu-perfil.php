<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Repositories\AuthRepository;

AuthMiddleware::check();

$pageTitle  = 'Meu Perfil';
$activePage = 'perfil';

$idUsuario = Session::getUsuarioId();
$repo      = new AuthRepository();
$usuario   = $repo->findById($idUsuario);

$perfisLabel = [
    'superadmin'    => 'Super Administrador',
    'administrador' => 'Administrador',
    'diretor'       => 'Diretor(a)',
    'escriturario'  => 'Escriturário(a)',
    'estagiario'    => 'Estagiário(a)',
];

$categoriasLabel = [
    'produto' => 'Produtos',
    'servico' => 'Serviços',
];

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <!-- Cabeçalho da página -->
  <div class="d-flex align-center justify-between mb-8">
    <div>
      <h2 class="text-h2">Meu Perfil</h2>
      <p class="text-body" style="color:var(--secondary); margin-top:4px;">
        Gerencie suas informações e segurança da conta.
      </p>
    </div>
  </div>

  <div class="grid-2" style="gap: var(--space-6); align-items: start;">

    <!-- Card: Informações da conta -->
    <div class="card" style="border-radius: var(--radius-xl);">
      <div class="card-header">
        <h3 class="card-title">Informações da Conta</h3>
        <span class="badge badge-<?= $usuario['perfil'] === 'superadmin' ? 'done' : ($usuario['perfil'] === 'administrador' ? 'analysis' : 'processing') ?>">
          <?= htmlspecialchars($perfisLabel[$usuario['perfil']] ?? $usuario['perfil']) ?>
        </span>
      </div>

      <div style="display: flex; flex-direction: column; gap: var(--space-4);">

        <!-- Avatar grande -->
        <div style="display:flex; align-items:center; gap: var(--space-4); padding: var(--space-4); background:var(--neutral-100); border-radius: var(--radius-lg);">
          <?php
          $iniciais = implode('', array_map(
              fn($p) => strtoupper($p[0]),
              array_slice(explode(' ', trim($usuario['nome'])), 0, 2)
          ));
          ?>
          <div class="user-avatar" style="width:56px; height:56px; font-size:20px; flex-shrink:0;">
            <?= htmlspecialchars($iniciais) ?>
          </div>
          <div>
            <div style="font-family:var(--font-primary); font-weight:var(--fw-bold); font-size:16px;">
              <?= htmlspecialchars($usuario['nome']) ?>
            </div>
            <div style="font-size:13px; color:var(--secondary); margin-top:2px;">
              @<?= htmlspecialchars($usuario['username']) ?>
            </div>
          </div>
        </div>

        <!-- Campos de info -->
        <?php
        $campos = [
            ['label' => 'Nome completo',   'value' => $usuario['nome']],
            ['label' => 'Nome de usuário', 'value' => '@' . $usuario['username']],
            ['label' => 'E-mail',          'value' => $usuario['email']],
            ['label' => 'Perfil',          'value' => $perfisLabel[$usuario['perfil']] ?? $usuario['perfil']],
        ];
        if ($usuario['perfil'] === 'administrador' && $usuario['categoria']) {
            $campos[] = ['label' => 'Categoria gerenciada', 'value' => $categoriasLabel[$usuario['categoria']] ?? $usuario['categoria']];
        }
        ?>
        <?php foreach ($campos as $campo): ?>
          <div style="display:flex; justify-content:space-between; align-items:center; padding: var(--space-3) 0; border-bottom: 1px solid var(--neutral-100);">
            <span style="font-size:13px; color:var(--secondary);"><?= $campo['label'] ?></span>
            <span style="font-size:14px; font-weight:var(--fw-medium);"><?= htmlspecialchars($campo['value'] ?? '—') ?></span>
          </div>
        <?php endforeach; ?>

      </div>
    </div>

    <!-- Card: Alterar senha -->
    <div class="card" style="border-radius: var(--radius-xl);">
      <div class="card-header">
        <h3 class="card-title">Alterar Senha</h3>
        <i data-lucide="shield" width="18" height="18" style="color:var(--secondary)"></i>
      </div>

      <!-- Alerta de resultado -->
      <div id="senhaAlert" style="display:none; margin-bottom:var(--space-4);"></div>

      <form id="formSenha" novalidate>

        <div class="form-group">
          <label class="form-label" for="senhaAtual">
            Senha atual <span class="required">*</span>
          </label>
          <div style="position:relative;">
            <input type="password" id="senhaAtual" name="senha_atual"
                   class="form-control" placeholder="••••••••"
                   autocomplete="current-password" maxlength="100">
            <button type="button" class="toggle-senha" data-target="senhaAtual"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                           background:none;border:none;color:var(--secondary);cursor:pointer;">
              <i data-lucide="eye" width="16" height="16"></i>
            </button>
          </div>
          <div class="form-error" id="errSenhaAtual"></div>
        </div>

        <div class="form-group">
          <label class="form-label" for="novaSenha">
            Nova senha <span class="required">*</span>
          </label>
          <div style="position:relative;">
            <input type="password" id="novaSenha" name="nova_senha"
                   class="form-control" placeholder="Mínimo 8 caracteres"
                   autocomplete="new-password" maxlength="100">
            <button type="button" class="toggle-senha" data-target="novaSenha"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                           background:none;border:none;color:var(--secondary);cursor:pointer;">
              <i data-lucide="eye" width="16" height="16"></i>
            </button>
          </div>
          <div class="form-hint">Mínimo 8 caracteres, 1 maiúscula e 1 número.</div>
          <div class="form-error" id="errNovaSenha"></div>
        </div>

        <!-- Indicador de força da senha -->
        <div style="margin-bottom: var(--space-5);">
          <div style="display:flex; gap:4px; margin-bottom:4px;">
            <div class="force-bar" data-index="0"></div>
            <div class="force-bar" data-index="1"></div>
            <div class="force-bar" data-index="2"></div>
            <div class="force-bar" data-index="3"></div>
          </div>
          <span id="forceLabel" style="font-size:11px; color:var(--secondary);"></span>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirmacao">
            Confirmar nova senha <span class="required">*</span>
          </label>
          <div style="position:relative;">
            <input type="password" id="confirmacao" name="confirmacao"
                   class="form-control" placeholder="Repita a nova senha"
                   autocomplete="new-password" maxlength="100">
            <button type="button" class="toggle-senha" data-target="confirmacao"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                           background:none;border:none;color:var(--secondary);cursor:pointer;">
              <i data-lucide="eye" width="16" height="16"></i>
            </button>
          </div>
          <div class="form-error" id="errConfirmacao"></div>
        </div>

        <button type="submit" class="btn btn-primary w-100" id="btnSalvar">
          Salvar nova senha
        </button>

      </form>
    </div>

  </div>
</div>

<style>
.force-bar {
  flex: 1;
  height: 4px;
  border-radius: 9999px;
  background: var(--neutral-200);
  transition: background 0.3s;
}
.force-bar.weak   { background: var(--brand); }
.force-bar.fair   { background: #f59e0b; }
.force-bar.good   { background: #3b82f6; }
.force-bar.strong { background: #16a34a; }
</style>

<script>
// Toggle visibilidade de senha
document.querySelectorAll('.toggle-senha').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    const icon  = btn.querySelector('[data-lucide]');
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    icon.setAttribute('data-lucide', isPass ? 'eye-off' : 'eye');
    if (window.lucide) lucide.createIcons();
  });
});

// Força da senha
document.getElementById('novaSenha').addEventListener('input', function () {
  const v     = this.value;
  const bars  = document.querySelectorAll('.force-bar');
  const label = document.getElementById('forceLabel');

  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const classes = ['', 'weak', 'fair', 'good', 'strong'];
  const labels  = ['', 'Fraca', 'Razoável', 'Boa', 'Forte'];
  const colors  = ['', 'var(--brand)', '#f59e0b', '#3b82f6', '#16a34a'];

  bars.forEach((b, i) => {
    b.className = 'force-bar';
    if (v.length > 0 && i < score) b.classList.add(classes[score]);
  });

  label.textContent = v.length > 0 ? `Força: ${labels[score]}` : '';
  label.style.color = colors[score];
});

// Submit
document.getElementById('formSenha').addEventListener('submit', async (e) => {
  e.preventDefault();

  // Limpa erros
  ['errSenhaAtual','errNovaSenha','errConfirmacao'].forEach(id => {
    document.getElementById(id).textContent = '';
  });

  const senhaAtual  = document.getElementById('senhaAtual').value;
  const novaSenha   = document.getElementById('novaSenha').value;
  const confirmacao = document.getElementById('confirmacao').value;

  let ok = true;
  if (!senhaAtual) { document.getElementById('errSenhaAtual').textContent = 'Informe a senha atual.'; ok = false; }
  if (!novaSenha)  { document.getElementById('errNovaSenha').textContent  = 'Informe a nova senha.'; ok = false; }
  if (!confirmacao){ document.getElementById('errConfirmacao').textContent = 'Confirme a nova senha.'; ok = false; }
  if (!ok) return;

  if (novaSenha !== confirmacao) {
    document.getElementById('errConfirmacao').textContent = 'As senhas não coincidem.';
    return;
  }

  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';

  try {
    const res  = await fetch('/api/auth/alterar-senha.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ senha_atual: senhaAtual, nova_senha: novaSenha, confirmacao })
    });
    const data = await res.json();

    const alert = document.getElementById('senhaAlert');
    alert.style.display = 'flex';

    if (data.success) {
      alert.className = 'alert alert-success animate-slide-up';
      alert.innerHTML = `<i data-lucide="check-circle" width="16" height="16"></i> ${data.message}`;
      document.getElementById('formSenha').reset();
      document.querySelectorAll('.force-bar').forEach(b => b.className = 'force-bar');
      document.getElementById('forceLabel').textContent = '';
    } else {
      alert.className = 'alert alert-danger animate-slide-up';
      alert.innerHTML = `<i data-lucide="alert-circle" width="16" height="16"></i> ${data.message}`;
    }
    if (window.lucide) lucide.createIcons();
  } catch (err) {
    const alert = document.getElementById('senhaAlert');
    alert.style.display = 'flex';
    alert.className = 'alert alert-danger';
    alert.textContent = 'Erro de conexão. Tente novamente.';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar nova senha';
  }
});
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
