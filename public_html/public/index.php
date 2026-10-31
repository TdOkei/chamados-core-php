<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

use App\Helpers\Session;
use App\Helpers\Response;

// Se já logado, redireciona
if (Session::isLoggedIn()) {
    Response::redirect('/dashboard.php');
}

$flashError = Session::getFlash('error');
$flashInfo  = Session::getFlash('info');

if (($_GET['sessao'] ?? '') === 'expirada') {
    $flashInfo = 'Sua sessao expirou por inatividade. Faca login novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#EDECEC">
  <link rel="icon" type="image/png" href="/favicon.png">
  <title>Entrar — Sistema de Pedidos</title>
  <link rel="stylesheet" href="/assets/css/design-system.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: var(--space-4);
      background: var(--body-bg);
    }

    /* Padrão de fundo sutil */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        radial-gradient(circle at 20% 20%, rgba(253,58,37,0.04) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(9,9,11,0.04) 0%, transparent 50%);
      pointer-events: none;
    }

    .login-wrap {
      width: 100%;
      max-width: 420px;
      animation: slideUp 0.4s ease both;
    }

    .login-brand {
      text-align: center;
      margin-bottom: var(--space-8);
    }

    .login-brand-logo {
      font-family: var(--font-primary);
      font-size: 36px;
      font-weight: var(--fw-extrabold);
      color: var(--primary);
      letter-spacing: -0.04em;
      line-height: 1;
    }

    .login-brand-logo span { color: var(--brand); }

    .login-brand-sub {
      font-size: 13px;
      color: var(--secondary);
      margin-top: var(--space-2);
    }

    .login-card {
      background: var(--white);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      padding: var(--space-8);
      border: 1px solid var(--neutral-200);
    }

    .login-title {
      font-family: var(--font-primary);
      font-size: 22px;
      font-weight: var(--fw-bold);
      margin-bottom: var(--space-1);
    }

    .login-subtitle {
      font-size: 14px;
      color: var(--secondary);
      margin-bottom: var(--space-6);
    }

    .input-icon-wrap {
      position: relative;
    }

    .input-icon-wrap .form-control {
      padding-left: 44px;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--secondary);
      pointer-events: none;
    }

    .btn-login {
      width: 100%;
      height: 48px;
      font-size: 15px;
      letter-spacing: 0.01em;
      margin-top: var(--space-4);
    }

    .login-footer {
      text-align: center;
      margin-top: var(--space-5);
      font-size: 12px;
      color: var(--secondary);
    }

    .bloqueado-hint {
      background: #fff0ee;
      border-radius: var(--radius-md);
      padding: var(--space-3) var(--space-4);
      font-size: 13px;
      color: #9a3412;
      margin-top: var(--space-3);
      display: none;
    }

    .bloqueado-hint.show { display: block; }

    .escola-hint {
      background: #eff6ff;
      border-radius: var(--radius-md);
      padding: var(--space-3) var(--space-4);
      font-size: 13px;
      color: #1e40af;
      margin-top: var(--space-3);
      display: none;
    }

    .escola-hint.show { display: block; }
  </style>
</head>
<body>

<div class="login-wrap">

  <!-- Brand -->
  <div class="login-brand">
    <img src="/assets/imgs/logo_Td_Okei.png" alt="TdOkei" style="height:56px;width:auto;object-fit:contain;margin-bottom:var(--space-2);margin-left:auto;margin-right:auto;display:block;">
    <div class="login-brand-sub">Sistema de gestão inteligente</div>
  </div>

  <!-- Alertas flash -->
  <?php if ($flashError): ?>
    <div class="alert alert-danger animate-fade-in" style="margin-bottom:var(--space-4);">
      <i data-lucide="alert-circle" width="16" height="16"></i>
      <?= htmlspecialchars($flashError) ?>
    </div>
  <?php endif; ?>
  <?php if ($flashInfo): ?>
    <div class="alert alert-info animate-fade-in" style="margin-bottom:var(--space-4);">
      <i data-lucide="info" width="16" height="16"></i>
      <?= htmlspecialchars($flashInfo) ?>
    </div>
  <?php endif; ?>

  <!-- Card de login -->
  <div class="login-card">
    <h1 class="login-title">Bem-vindo(a)</h1>
    <p class="login-subtitle">Acesse com suas credenciais para continuar.</p>

    <form id="loginForm" novalidate>
      <!-- Username -->
      <div class="form-group">
        <label class="form-label" for="username">Usuário</label>
        <div class="input-icon-wrap">
          <i data-lucide="user" width="16" height="16" class="input-icon"></i>
          <input
            type="text"
            id="username"
            name="username"
            class="form-control"
            placeholder="nome.usuario"
            autocomplete="username"
            required
            maxlength="60">
        </div>
        <div class="form-error" id="usernameError"></div>
      </div>

      <!-- Senha -->
      <div class="form-group">
        <label class="form-label" for="senha">Senha</label>
        <div class="input-icon-wrap" style="position:relative;">
          <i data-lucide="lock" width="16" height="16" class="input-icon"></i>
          <input
            type="password"
            id="senha"
            name="senha"
            class="form-control"
            placeholder="••••••••"
            autocomplete="current-password"
            required
            maxlength="100"
            style="padding-right:44px;">
          <button type="button" id="toggleSenha"
            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                   background:none;border:none;color:var(--secondary);cursor:pointer;padding:4px;"
            aria-label="Mostrar senha">
            <i data-lucide="eye" width="16" height="16" id="eyeIcon"></i>
          </button>
        </div>
        <div class="form-error" id="senhaError"></div>
      </div>

      <!-- Dica de conta bloqueada -->
      <div class="bloqueado-hint" id="bloqueadoHint">
        <strong>Conta bloqueada.</strong> Muitas tentativas incorretas.
        Entre em contato com o administrador do sistema para redefinir sua senha.
      </div>

      <!-- Aviso de escola indisponivel -->
      <div class="escola-hint" id="escolaHint">
        <strong>Acesso autenticado.</strong> <span id="escolaHintMsg"></span>
      </div>

      <button type="submit" class="btn btn-primary btn-login" id="btnLogin">
        <span id="btnLoginText">Entrar</span>
        <span id="btnLoginSpinner" style="display:none;">
          <span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>
        </span>
      </button>
    </form>
  </div>

  <div class="login-footer">
    Td Okei &copy; <?= date('Y') ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();
});

// Toggle senha
document.getElementById('toggleSenha')?.addEventListener('click', () => {
  const input   = document.getElementById('senha');
  const icon    = document.getElementById('eyeIcon');
  const isPass  = input.type === 'password';
  input.type    = isPass ? 'text' : 'password';
  icon.setAttribute('data-lucide', isPass ? 'eye-off' : 'eye');
  if (window.lucide) lucide.createIcons();
});

// Submit
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const username = document.getElementById('username').value.trim();
  const senha    = document.getElementById('senha').value;

  // Reset erros
  document.getElementById('usernameError').textContent = '';
  document.getElementById('senhaError').textContent    = '';
  document.getElementById('bloqueadoHint').classList.remove('show');
  document.getElementById('escolaHint').classList.remove('show');

  // Validação básica
  let hasError = false;
  if (!username) {
    document.getElementById('usernameError').textContent = 'Informe o usuário.';
    document.getElementById('username').classList.add('is-invalid');
    hasError = true;
  }
  if (!senha) {
    document.getElementById('senhaError').textContent = 'Informe a senha.';
    document.getElementById('senha').classList.add('is-invalid');
    hasError = true;
  }
  if (hasError) return;

  // Loading
  const btn     = document.getElementById('btnLogin');
  const btnText = document.getElementById('btnLoginText');
  const spinner = document.getElementById('btnLoginSpinner');
  btn.disabled  = true;
  btnText.style.display  = 'none';
  spinner.style.display  = 'inline-flex';

  try {
    const res  = await fetch('/api/auth/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, senha }),
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = '/dashboard.php';
    } else {
      if (data.data?.bloqueado) {
        document.getElementById('bloqueadoHint').classList.add('show');
      } else if (data.data?.escola_inativa) {
        document.getElementById('escolaHintMsg').textContent = data.message;
        document.getElementById('escolaHint').classList.add('show');
      } else {
        document.getElementById('senhaError').textContent = data.message || 'Usuário ou senha incorretos.';
        document.getElementById('senha').classList.add('is-invalid');
      }
    }
  } catch (err) {
    document.getElementById('senhaError').textContent = 'Erro de conexão. Tente novamente.';
  } finally {
    btn.disabled = false;
    btnText.style.display  = 'inline';
    spinner.style.display  = 'none';
  }
});

// Remove classe is-invalid ao digitar
['username', 'senha'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', () => {
    document.getElementById(id).classList.remove('is-invalid');
  });
});
</script>

</body>
</html>
