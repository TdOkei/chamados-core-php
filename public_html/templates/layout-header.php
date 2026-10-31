<?php
/**
 * Layout principal do sistema
 * Uso: incluir este arquivo após definir $pageTitle e $activePage
 */

use App\Helpers\Session;
use App\Config\App;

$perfil    = Session::getPerfil();
$nome      = Session::get('usuario_nome', 'Usuário');
$iniciais  = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', trim($nome)), 0, 2)));

// Menus por perfil
$menus = [
    App::PERFIL_SUPERADMIN => [
        ['icon' => 'grid', 'label' => 'Dashboard',     'page' => 'dashboard',     'href' => '/dashboard.php'],
        ['section' => 'Cadastros'],
        ['icon' => 'school', 'label' => 'Escolas',        'page' => 'escolas',       'href' => '/pages/escolas.php'],
        ['icon' => 'users', 'label' => 'Usuários',       'page' => 'usuarios',      'href' => '/pages/usuarios.php'],
        ['icon' => 'box',   'label' => 'Produtos',       'page' => 'produtos',      'href' => '/pages/produtos.php'],
        ['icon' => 'ruler', 'label' => 'Unid. de Medida','page' => 'unidades',      'href' => '/pages/unidades.php'],
        ['section' => 'Gestão'],
        ['icon' => 'inbox',  'label' => 'Sugestões',      'page' => 'sugestoes',     'href' => '/pages/sugestoes.php', 'badge' => true],
        ['icon' => 'list',        'label' => 'Histórico',   'page' => 'historico',  'href' => '/pages/historico.php'],
        ['icon' => 'bar-chart',    'label' => 'Relatórios',  'page' => 'relatorios', 'href' => '/pages/relatorios.php'],
        ['icon' => 'shield-check', 'label' => 'Auditoria',   'page' => 'auditoria',  'href' => '/pages/auditoria.php'],
        ['icon' => 'image',        'label' => 'Imagens',     'page' => 'gestao-imagens', 'href' => '/pages/gestao-imagens.php'],
        ['icon' => 'settings',     'label' => 'Remessa',     'page' => 'configuracoes', 'href' => '/pages/configuracoes.php'],
    ],
    App::PERFIL_ADMINISTRADOR => [
        ['icon' => 'grid',   'label' => 'Dashboard',     'page' => 'dashboard',     'href' => '/dashboard.php'],
        ['section' => 'Solicitações'],
        ['icon' => 'list',   'label' => 'Fila de Pedidos','page' => 'fila',         'href' => '/pages/fila.php', 'badge' => true],
        ['section' => 'Oportunidades'],
        ['icon' => 'gift',   'label' => 'Oportunidades',  'page' => 'oportunidades', 'href' => '/pages/oportunidades.php'],
    ],
    


    App::PERFIL_SECRETARIO => [
        ['icon' => 'list',      'label' => 'Historico',  'page' => 'historico',  'href' => '/pages/historico.php'],
        ['icon' => 'bar-chart', 'label' => 'Relatorios', 'page' => 'relatorios', 'href' => '/pages/relatorios.php'],
    ],
    App::PERFIL_DIRETOR => [
        ['icon' => 'grid',       'label' => 'Dashboard',          'page' => 'dashboard',         'href' => '/dashboard.php'],
        ['section' => 'Solicitações'],
        ['icon' => 'plus-circle','label' => 'Nova Solicitação',   'page' => 'nova-solicitacao',  'href' => '/pages/nova-solicitacao.php'],
        ['icon' => 'list',       'label' => 'Minhas Solicitações','page' => 'solicitacoes',      'href' => '/pages/solicitacoes.php'],
        ['icon' => 'qr-code',    'label' => 'Ler QR Code',        'page' => 'qr-leitura',        'href' => '/pages/qr-leitura.php'],
        ['section' => 'Oportunidades'],
        ['icon' => 'gift',       'label' => 'Oportunidades',      'page' => 'oportunidades',     'href' => '/pages/oportunidades-diretor.php', 'badge' => true],
        ['section' => 'Outros'],
        ['icon' => 'package',    'label' => 'Sugerir Produto',    'page' => 'sugerir',           'href' => '/pages/sugerir-produto.php'],
    ],
];

$navItems = $menus[$perfil] ?? (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) ? $menus[App::PERFIL_DIRETOR] : []);

// Perfis de escola sem acesso a Oportunidades (ex.: estagiário): remove a seção e o item do menu
if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true) && !in_array($perfil, App::PERFIS_OPORTUNIDADES_ESCOLA, true)) {
    $navItems = array_values(array_filter($navItems, function ($item) {
        if (($item['section'] ?? '') === 'Oportunidades') return false;
        if (($item['page'] ?? '') === 'oportunidades')    return false;
        return true;
    }));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#EDECEC">
  <link rel="icon" type="image/png" href="/favicon.png">
  <title><?= htmlspecialchars($pageTitle ?? 'Sistema de Pedidos') ?> — Secretaria de Ensino</title>

  <!-- Design System -->
  <link rel="stylesheet" href="/assets/css/design-system.css">

  <!-- Lucide Icons (via CDN) -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>

  <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body>

<!-- Overlay sidebar (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-shell">

  <!-- ======== SIDEBAR ======== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <img src="/assets/imgs/logo_Td_Okei.png" alt="TdOkei" style="height:32px;width:auto;object-fit:contain;">
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($navItems as $item): ?>
        <?php if (isset($item['section'])): ?>
          <div class="nav-section-label"><?= htmlspecialchars($item['section']) ?></div>
        <?php else: ?>
          <a href="<?= $item['href'] ?>"
             class="nav-item <?= ($activePage ?? '') === $item['page'] ? 'active' : '' ?>"
             <?= isset($item['badge']) ? 'data-badge-nav="' . $item['page'] . '"' : '' ?>>
            <span class="nav-icon">
              <i data-lucide="<?= $item['icon'] ?>" width="18" height="18"></i>
            </span>
            <?= htmlspecialchars($item['label']) ?>
            <?php if (isset($item['badge'])): ?>
              <span class="nav-badge" id="badge-<?= $item['page'] ?>" style="display:none">0</span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= htmlspecialchars($iniciais) ?></div>
        <div class="user-details">
          <div class="user-name"><?= htmlspecialchars($nome) ?></div>
          <div class="user-role">
            <?php
            $labels = [
                'superadmin'    => 'Super Admin',
                'administrador' => 'Administrador',
                'diretor'       => 'Diretor(a)',
                'escriturario'  => 'Escriturário(a)',
            ];
            echo htmlspecialchars($labels[$perfil] ?? $perfil);
            ?>
          </div>
        </div>
        <a href="/api/auth/logout.php" class="btn btn-ghost btn-icon-sm" title="Sair">
          <i data-lucide="log-out" width="15" height="15"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- ======== MAIN ======== -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <button class="btn-menu" id="btnMenu" aria-label="Menu">
        <i data-lucide="menu" width="20" height="20"></i>
      </button>

      <h1 class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></h1>

      <div class="topbar-actions">
        <!-- Notificações -->
        <button class="notif-btn" id="btnNotif" title="Notificações">
          <i data-lucide="bell" width="18" height="18"></i>
          <span class="notif-count" id="notifCount" style="display:none">0</span>
        </button>

        <!-- Perfil -->
        <a href="/pages/meu-perfil.php"
           class="btn btn-outline btn-sm"
           style="display:flex;align-items:center;gap:6px;">
          <div class="user-avatar" style="width:24px;height:24px;font-size:10px;">
            <?= htmlspecialchars($iniciais) ?>
          </div>
          <span class="d-none d-sm-inline"><?= htmlspecialchars(explode(' ', $nome)[0]) ?></span>
        </a>
      </div>
    </header>

    <!-- Flash messages -->
    <?php
    $flashError   = Session::getFlash('error');
    $flashSuccess = Session::getFlash('success');
    $flashInfo    = Session::getFlash('info');
    ?>
    <?php if ($flashError): ?>
      <div style="padding: 0 var(--space-6); margin-top: var(--space-4);">
        <div class="alert alert-danger animate-slide-up">
          <i data-lucide="alert-circle" width="16" height="16"></i>
          <?= htmlspecialchars($flashError) ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
      <div style="padding: 0 var(--space-6); margin-top: var(--space-4);">
        <div class="alert alert-success animate-slide-up">
          <i data-lucide="check-circle" width="16" height="16"></i>
          <?= htmlspecialchars($flashSuccess) ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($flashInfo): ?>
      <div style="padding: 0 var(--space-6); margin-top: var(--space-4);">
        <div class="alert alert-info animate-slide-up">
          <i data-lucide="info" width="16" height="16"></i>
          <?= htmlspecialchars($flashInfo) ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Conteúdo da página -->
    <main class="page-content">
