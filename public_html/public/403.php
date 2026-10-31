<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 — Acesso Negado</title>
  <link rel="stylesheet" href="/assets/css/design-system.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:var(--space-6); }
  </style>
</head>
<body>
  <div style="text-align:center; max-width:400px; animation: slideUp .4s ease both;">
    <div style="width:80px;height:80px;border-radius:var(--radius-xl);background:#fee2e2;
                display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-5);">
      <i data-lucide="shield-off" width="40" height="40" style="color:var(--brand);"></i>
    </div>
    <div style="font-family:var(--font-primary);font-size:72px;font-weight:800;color:var(--neutral-200);line-height:1;">
      403
    </div>
    <h1 style="font-family:var(--font-primary);font-size:24px;font-weight:700;margin:var(--space-3) 0 var(--space-2);">
      Acesso negado
    </h1>
    <p style="color:var(--secondary);font-size:14px;margin-bottom:var(--space-6);">
      Você não tem permissão para acessar esta página.<br>
      Faça login com uma conta que tenha acesso.
    </p>
    <div style="display:flex;gap:var(--space-3);justify-content:center;flex-wrap:wrap;">
      <a href="/index.php" class="btn btn-primary">
        <i data-lucide="log-in" width="16" height="16"></i> Fazer login
      </a>
      <a href="javascript:history.back()" class="btn btn-outline">
        <i data-lucide="arrow-left" width="16" height="16"></i> Voltar
      </a>
    </div>
  </div>
  <script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
</body>
</html>
