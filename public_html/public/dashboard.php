<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Helpers\Response;
use App\Config\App;
use App\Config\Database;

AuthMiddleware::check();

$perfil    = Session::getPerfil();
$pageTitle = 'Dashboard';
$activePage= 'dashboard';
$db        = Database::getInstance();
$idUsuario = Session::getUsuarioId();
$idEscola  = Session::getIdEscola();
$categoria = Session::getCategoria();

// ----------------------------------------------------------------
// Carrega estatísticas conforme perfil
// ----------------------------------------------------------------
$stats = [];

if ($perfil === App::PERFIL_SUPERADMIN) {
    $stats['escolas']      = (int) $db->query('SELECT COUNT(*) FROM escolas WHERE ativo = 1')->fetchColumn();
    $stats['usuarios']     = (int) $db->query('SELECT COUNT(*) FROM usuarios WHERE ativo = 1 AND perfil != "superadmin"')->fetchColumn();
    $stats['pendentes']    = (int) $db->query('SELECT COUNT(*) FROM solicitacoes WHERE status = "pendente"')->fetchColumn();
    $stats['concluidos']   = (int) $db->query('SELECT COUNT(*) FROM solicitacoes WHERE status = "concluido"')->fetchColumn();
    $stats['sugestoes']    = (int) $db->query('SELECT COUNT(*) FROM sugestoes_produto WHERE status = "pendente"')->fetchColumn();

    // Solicitações recentes
    $recentes = $db->query(
        'SELECT s.id, s.categoria, s.status, s.created_at,
                e.nome AS escola, u.nome AS diretor
         FROM solicitacoes s
         JOIN escolas e ON e.id = s.id_escola
         JOIN usuarios u ON u.id = s.id_diretor
         ORDER BY s.created_at DESC
         LIMIT 8'
    )->fetchAll();

} elseif ($perfil === App::PERFIL_ADMINISTRADOR) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM solicitacoes WHERE categoria = :cat AND status = :s');

    foreach (['pendente','em_analise','em_processamento','despachado'] as $s) {
        $stmt->execute([':cat' => $categoria, ':s' => $s]);
        $stats[$s] = (int) $stmt->fetchColumn();
    }

    // Solicitações da categoria
    $stmtR = $db->prepare(
        'SELECT s.id, s.categoria, s.status, s.created_at,
                e.nome AS escola
         FROM solicitacoes s
         JOIN escolas e ON e.id = s.id_escola
         WHERE s.categoria = :cat AND s.status NOT IN ("concluido","cancelado","rejeitado")
         ORDER BY s.created_at ASC
         LIMIT 8'
    );
    $stmtR->execute([':cat' => $categoria]);
    $recentes = $stmtR->fetchAll();

} elseif ($perfil === App::PERFIL_SECRETARIO) {
    header('Location: /pages/historico.php');
    exit;
}

if (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM solicitacoes WHERE id_escola = :esc AND status = :s');

    foreach (['pendente','em_analise','em_processamento','despachado','concluido'] as $s) {
        $stmt->execute([':esc' => $idEscola, ':s' => $s]);
        $stats[$s] = (int) $stmt->fetchColumn();
    }

    // Minhas solicitações recentes
    $stmtR = $db->prepare(
        'SELECT id, categoria, status, created_at
         FROM solicitacoes
         WHERE id_escola = :esc
         ORDER BY created_at DESC
         LIMIT 8'
    );
    $stmtR->execute([':esc' => $idEscola]);
    $recentes = $stmtR->fetchAll();

    // Oportunidades não decididas
    $stmtO = $db->prepare(
        'SELECT COUNT(*) FROM oportunidade_escolas oe
         JOIN oportunidades o ON o.id = oe.id_oportunidade
         WHERE oe.id_escola = :esc AND oe.decisao = "pendente"
         AND o.status = "ativa"'
    );
    $stmtO->execute([':esc' => $idEscola]);
    $stats['oportunidades_pendentes'] = (int) $stmtO->fetchColumn();
}

require_once __DIR__ . '/../templates/layout-header.php';
?>

<div class="animate-fade-in">

<!-- ============================
     SUPERADMIN DASHBOARD
     ============================ -->
<?php if ($perfil === App::PERFIL_SUPERADMIN): ?>

  <div class="mb-8">
    <h2 class="text-h2">Visão Geral do Sistema</h2>
    <p class="text-body" style="color:var(--secondary); margin-top:4px;">
      Bem-vindo(a), <?= htmlspecialchars(explode(' ', Session::get('usuario_nome'))[0]) ?>. Aqui está o resumo atual.
    </p>
  </div>

  <!-- Stats -->
  <div class="stats-grid stagger">
    <div class="stat-card animate-slide-up">
      <div class="stat-icon stat-icon-dark">
        <i data-lucide="school" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value"><?= $stats['escolas'] ?></div>
        <div class="stat-label">Escolas ativas</div>
      </div>
    </div>
    <div class="stat-card animate-slide-up">
      <div class="stat-icon stat-icon-dark">
        <i data-lucide="users" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value"><?= $stats['usuarios'] ?></div>
        <div class="stat-label">Usuários cadastrados</div>
      </div>
    </div>
    <div class="stat-card animate-slide-up">
      <div class="stat-icon stat-icon-brand">
        <i data-lucide="clock" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value"><?= $stats['pendentes'] ?></div>
        <div class="stat-label">Solicitações pendentes</div>
      </div>
    </div>
    <div class="stat-card animate-slide-up">
      <div class="stat-icon stat-icon-green">
        <i data-lucide="check-circle" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value"><?= $stats['concluidos'] ?></div>
        <div class="stat-label">Concluídas</div>
      </div>
    </div>
    <?php if ($stats['sugestoes'] > 0): ?>
    <div class="stat-card animate-slide-up" style="cursor:pointer;" onclick="window.location='/pages/sugestoes.php'">
      <div class="stat-icon stat-icon-amber">
        <i data-lucide="lightbulb" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value"><?= $stats['sugestoes'] ?></div>
        <div class="stat-label">Sugestões pendentes</div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Solicitações recentes -->
  <div class="card" style="border-radius:var(--radius-xl);">
    <div class="card-header">
      <h3 class="card-title">Solicitações Recentes</h3>
      <a href="/pages/historico.php" class="btn btn-ghost btn-sm">Ver histórico</a>
    </div>
    <?= renderTabelaSolicitacoes($recentes, $perfil) ?>
  </div>


<!-- ============================
     ADMINISTRADOR DASHBOARD
     ============================ -->
<?php elseif ($perfil === App::PERFIL_ADMINISTRADOR):
  $catLabel = $categoria === 'produto' ? 'Produtos' : 'Serviços';
?>

  <div class="mb-8">
    <h2 class="text-h2">Fila de <?= $catLabel ?></h2>
    <p class="text-body" style="color:var(--secondary); margin-top:4px;">
      Gerencie as solicitações de <?= $catLabel ?> da rede de escolas.
    </p>
  </div>

  <div class="stats-grid stagger">
    <?php
    $adminStats = [
        'pendente'          => ['label' => 'Aguardando análise',  'icon' => 'inbox',       'class' => 'stat-icon-brand'],
        'em_analise'        => ['label' => 'Em análise',          'icon' => 'search',      'class' => 'stat-icon-dark'],
        'em_processamento'  => ['label' => 'Em processamento',    'icon' => 'settings',    'class' => 'stat-icon-dark'],
        'despachado'        => ['label' => 'Despachados',         'icon' => 'truck',       'class' => 'stat-icon-green'],
    ];
    foreach ($adminStats as $key => $info):
    ?>
    <div class="stat-card animate-slide-up">
      <div class="stat-icon <?= $info['class'] ?>">
        <i data-lucide="<?= $info['icon'] ?>" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value"><?= $stats[$key] ?? 0 ?></div>
        <div class="stat-label"><?= $info['label'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card" style="border-radius:var(--radius-xl);">
    <div class="card-header">
      <h3 class="card-title">Próximas da Fila</h3>
      <a href="/pages/fila.php" class="btn btn-brand btn-sm">Ver fila completa</a>
    </div>
    <?= renderTabelaSolicitacoes($recentes, $perfil) ?>
  </div>


<!-- ============================
     DIRETOR DASHBOARD
     ============================ -->
<?php elseif (in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)): ?>

  <div class="mb-8">
    <h2 class="text-h2">Minhas Solicitações</h2>
    <p class="text-body" style="color:var(--secondary); margin-top:4px;">
      Acompanhe o andamento dos seus pedidos junto à Secretaria de Ensino.
    </p>
  </div>

  <div class="stats-grid stagger">
    <?php
    $diretorStats = [
        'pendente'         => ['label' => 'Pendentes',        'icon' => 'clock',        'class' => 'stat-icon-brand'],
        'em_analise'       => ['label' => 'Em análise',       'icon' => 'search',       'class' => 'stat-icon-dark'],
        'em_processamento' => ['label' => 'Em processamento', 'icon' => 'settings',     'class' => 'stat-icon-dark'],
        'despachado'       => ['label' => 'A caminho',        'icon' => 'truck',        'class' => 'stat-icon-amber'],
        'concluido'        => ['label' => 'Concluídas',       'icon' => 'check-circle', 'class' => 'stat-icon-green'],
    ];
    foreach ($diretorStats as $key => $info):
    ?>
    <div class="stat-card animate-slide-up">
      <div class="stat-icon <?= $info['class'] ?>">
        <i data-lucide="<?= $info['icon'] ?>" width="22" height="22"></i>
      </div>
      <div>
        <div class="stat-value"><?= $stats[$key] ?? 0 ?></div>
        <div class="stat-label"><?= $info['label'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Banner de oportunidades pendentes -->
  <?php if (!empty($stats['oportunidades_pendentes']) && $stats['oportunidades_pendentes'] > 0): ?>
  <div class="card-brand animate-slide-up mb-6" style="border-radius:var(--radius-xl); display:flex; align-items:center; gap:var(--space-4); justify-content:space-between; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:var(--space-3);">
      <i data-lucide="gift" width="24" height="24"></i>
      <div>
        <div style="font-family:var(--font-primary); font-weight:var(--fw-bold); font-size:16px;">
          <?= $stats['oportunidades_pendentes'] ?> oportunidade<?= $stats['oportunidades_pendentes'] > 1 ? 's' : '' ?> aguardando sua resposta
        </div>
        <div style="font-size:13px; opacity:0.85; margin-top:2px;">
          A Secretaria enviou oportunidades para sua escola.
        </div>
      </div>
    </div>
    <a href="/pages/oportunidades-diretor.php" class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,0.5); color:var(--white); background:rgba(255,255,255,0.15);">
      Ver oportunidades
    </a>
  </div>
  <?php endif; ?>

  <div class="card" style="border-radius:var(--radius-xl);">
    <div class="card-header">
      <h3 class="card-title">Pedidos Recentes</h3>
      <a href="/pages/solicitacoes.php" class="btn btn-ghost btn-sm">Ver todos</a>
    </div>
    <?= renderTabelaSolicitacoes($recentes, $perfil) ?>
  </div>

  <!-- Ação rápida -->
  <div class="card-flat mt-4 animate-slide-up" style="border-radius:var(--radius-xl); text-align:center; padding: var(--space-8);">
    <i data-lucide="plus-circle" width="32" height="32" style="color:var(--secondary); margin:0 auto var(--space-3);"></i>
    <div style="font-family:var(--font-primary); font-size:16px; font-weight:var(--fw-semibold); margin-bottom:var(--space-2);">Nova solicitação</div>
    <p class="text-body" style="color:var(--secondary); margin-bottom:var(--space-4);">Solicite produtos ou serviços para sua escola.</p>
    <a href="/pages/nova-solicitacao.php" class="btn btn-primary">
      <i data-lucide="plus" width="16" height="16"></i>
      Criar solicitação
    </a>
  </div>

<?php endif; ?>
</div>

<?php

// ---- Helper: renderiza tabela de solicitações ----
function renderTabelaSolicitacoes(array $rows, string $perfil): string
{
    if (empty($rows)) {
        return '<div class="empty-state"><div class="empty-state-icon"><i data-lucide="inbox" width="28" height="28"></i></div><p class="empty-state-text">Nenhuma solicitação encontrada.</p></div>';
    }

    $labels = \App\Config\App::STATUS_LABELS;
    $colors = \App\Config\App::STATUS_COLORS;

    $html = '<div class="table-wrapper"><table class="table"><thead><tr>';
    $html .= '<th>#</th>';
    if (!in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)) $html .= '<th>Escola</th>';
    $html .= '<th>Categoria</th><th>Status</th><th>Data</th><th></th></tr></thead><tbody>';

    foreach ($rows as $r) {
        $statusLabel = $labels[$r['status']] ?? $r['status'];
        $statusColor = $colors[$r['status']] ?? '';
        $cat         = $r['categoria'] ?? 'produto';
        $catLabel    = $cat === 'produto' ? 'Produto' : 'Serviço';
        $catBadge    = 'badge-' . $cat;
        $data        = date('d/m/Y H:i', strtotime($r['created_at']));

        $html .= "<tr>";
        $html .= "<td><span style='font-family:var(--font-primary);font-weight:var(--fw-semibold);'>#" . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT) . "</span></td>";
        if (!in_array($perfil, App::PERFIS_OPERACIONAIS_ESCOLA, true)) $html .= "<td>" . htmlspecialchars($r['escola'] ?? '—') . "</td>";
        $html .= "<td><span class='badge {$catBadge}'>{$catLabel}</span></td>";
        $html .= "<td><span class='badge {$statusColor}'>{$statusLabel}</span></td>";
        $html .= "<td style='color:var(--secondary);font-size:13px;'>{$data}</td>";
        $html .= "<td><a href='/pages/solicitacao-detalhe.php?id={$r['id']}' class='btn btn-ghost btn-sm'>Ver</a></td>";
        $html .= "</tr>";
    }

    $html .= '</tbody></table></div>';
    return $html;
}

require_once __DIR__ . '/../templates/layout-footer.php';
?>
