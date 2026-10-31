<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — Página não encontrada</title>
  <link rel="stylesheet" href="/assets/css/design-system.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:var(--space-6); }
  </style>
</head>
<body>
  <div style="text-align:center; max-width:420px; animation: slideUp .4s ease both;">
    <div style="font-family:var(--font-primary);font-size:96px;font-weight:800;
                color:var(--neutral-200);line-height:1;letter-spacing:-0.04em;">
      404
    </div>
    <div style="width:64px;height:4px;background:var(--brand);border-radius:2px;margin:0 auto var(--space-5);"></div>
    <h1 style="font-family:var(--font-primary);font-size:22px;font-weight:700;margin-bottom:var(--space-2);">
      Página não encontrada
    </h1>
    <p style="color:var(--secondary);font-size:14px;margin-bottom:var(--space-6);line-height:1.6;">
      A página que você está procurando não existe ou foi movida.
    </p>
    <div style="display:flex;gap:var(--space-3);justify-content:center;flex-wrap:wrap;">
      <a href="/dashboard.php" class="btn btn-primary">
        <i data-lucide="home" width="16" height="16"></i> Ir para o início
      </a>
      <a href="javascript:history.back()" class="btn btn-outline">
        <i data-lucide="arrow-left" width="16" height="16"></i> Voltar
      </a>
    </div>
  </div>
  <script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
</body>
</html>
