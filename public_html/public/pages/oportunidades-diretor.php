<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Session;
use App\Services\OportunidadeService;

AuthMiddleware::requireDiretor();

$pageTitle    = 'Oportunidades';
$activePage   = 'oportunidades';
$idEscola     = Session::getIdEscola();
$service      = new OportunidadeService();
$oportunidades= $service->listarParaEscola($idEscola);
$historico    = $service->buscarHistoricoEscola($idEscola);

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="mb-6">
    <h2 class="text-h2">Oportunidades</h2>
    <p class="text-body" style="color:var(--secondary);margin-top:4px;">
      Oportunidades enviadas pela Secretaria de Ensino para sua escola.
    </p>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:4px;background:var(--neutral-100);border-radius:var(--radius-lg);padding:4px;margin-bottom:var(--space-6);width:fit-content;">
    <button class="tab-btn active" onclick="mudarTab('pendentes',this)">
      Disponíveis
      <?php if (count($oportunidades) > 0): ?>
        <span class="nav-badge" style="position:static;display:inline-flex;margin-left:6px;"><?= count($oportunidades) ?></span>
      <?php endif; ?>
    </button>
    <button class="tab-btn" onclick="mudarTab('historico',this)">Histórico</button>
  </div>

  <!-- Oportunidades disponíveis -->
  <div id="tab-pendentes">
    <?php if (empty($oportunidades)): ?>
    <div class="card-flat" style="border-radius:var(--radius-xl);">
      <div class="empty-state" style="padding:var(--space-12);">
        <div class="empty-state-icon"><i data-lucide="gift" width="32" height="32"></i></div>
        <p class="empty-state-title">Nenhuma oportunidade disponível</p>
        <p class="empty-state-text">Quando a Secretaria enviar oportunidades para sua escola, elas aparecerão aqui.</p>
      </div>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);" class="stagger">
      <?php foreach ($oportunidades as $op): ?>
      <div class="card animate-slide-up" style="border-radius:var(--radius-xl);">
        <div style="display:flex;gap:var(--space-4);align-items:flex-start;flex-wrap:wrap;">

          <!-- Ícone -->
          <div style="width:48px;height:48px;border-radius:var(--radius-md);background:var(--brand-light);
                      display:flex;align-items:center;justify-content:center;color:var(--brand);flex-shrink:0;">
            <i data-lucide="gift" width="22" height="22"></i>
          </div>

          <!-- Conteúdo -->
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-2);flex-wrap:wrap;">
              <span style="font-family:var(--font-primary);font-weight:var(--fw-bold);font-size:16px;">
                <?= htmlspecialchars($op['titulo']) ?>
              </span>
              <span class="badge badge-<?= $op['categoria'] ?>">
                <?= $op['categoria'] === 'produto' ? 'Produto' : 'Serviço' ?>
              </span>
            </div>
            <p style="font-size:14px;color:var(--secondary);margin-bottom:var(--space-3);line-height:1.6;">
              <?= nl2br(htmlspecialchars($op['descricao'])) ?>
            </p>
            <div style="font-size:12px;color:var(--neutral-300);">
              Enviada por <?= htmlspecialchars($op['admin_nome']) ?> · <?= date('d/m/Y', strtotime($op['created_at'])) ?>
            </div>
          </div>

          <!-- Botões de decisão -->
          <div style="display:flex;flex-direction:column;gap:var(--space-2);flex-shrink:0;">
            <button class="btn btn-primary btn-sm" onclick="decidir(<?= $op['id'] ?>, 'aceita', this, <?= (int)($op['pedir_quantidade'] ?? 0) ?>, '<?= htmlspecialchars($op['unidade_sigla'] ?? '') ?>')">
              <i data-lucide="check" width="14" height="14"></i> Aceitar
            </button>
            <button class="btn btn-outline btn-sm" onclick="decidir(<?= $op['id'] ?>, 'rejeitada', this)"
                    style="border-color:var(--brand);color:var(--brand);">
              <i data-lucide="x" width="14" height="14"></i> Rejeitar
            </button>
          </div>

        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Histórico -->
  <div id="tab-historico" style="display:none;">
    <?php if (empty($historico)): ?>
    <div class="card-flat" style="border-radius:var(--radius-xl);">
      <div class="empty-state" style="padding:var(--space-10);">
        <p class="empty-state-text">Nenhuma decisão tomada ainda.</p>
      </div>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Oportunidade</th>
            <th>Categoria</th>
            <th>Decisão</th>
            <th>Data da Decisão</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($historico as $h): ?>
          <tr>
            <td style="font-weight:var(--fw-semibold);"><?= htmlspecialchars($h['titulo']) ?></td>
            <td><span class="badge badge-<?= $h['categoria'] ?>"><?= $h['categoria'] === 'produto' ? 'Produto' : 'Serviço' ?></span></td>
            <td>
              <span class="badge <?= $h['decisao'] === 'aceita' ? 'badge-done' : 'badge-rejected' ?>">
                <?= $h['decisao'] === 'aceita' ? 'Aceita' : 'Rejeitada' ?>
              </span>
            </td>
            <td style="font-size:13px;color:var(--secondary);">
              <?= $h['decidido_em'] ? date('d/m/Y H:i', strtotime($h['decidido_em'])) : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Modal confirmação -->
<div class="modal-overlay" id="modalConfirmar" style="display:none;" onclick="if(event.target===this)fecharConfirmar()">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h3 class="modal-title" id="confirmarTitulo">Confirmar ação</h3>
      <button class="btn btn-ghost btn-icon" onclick="fecharConfirmar()"><i data-lucide="x" width="18" height="18"></i></button>
    </div>
    <div class="modal-body">
      <p id="confirmarMsg" class="text-body" style="color:var(--secondary);margin-bottom:var(--space-4);"></p>
      <div id="blocoQuantidade" style="display:none;">
        <label class="form-label">Quantidade desejada <span class="required">*</span></label>
        <div style="display:flex;align-items:center;gap:var(--space-2);">
          <input type="number" id="inputQuantidade" class="form-control" min="0.01" step="0.01"
                 placeholder="0,00" style="flex:1;">
          <span id="labelUnidade" style="font-size:14px;font-weight:var(--fw-semibold);
                color:var(--secondary);min-width:40px;"></span>
        </div>
        <div class="form-hint" style="margin-top:var(--space-2);">
          Informe a quantidade que sua escola necessita.
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharConfirmar()">Cancelar</button>
      <button class="btn btn-primary" id="btnConfirmarDecisao">Confirmar</button>
    </div>
  </div>
</div>

<style>
.tab-btn {
  background:none; border:none; padding:8px 16px;
  font-family:var(--font-primary); font-size:13px; font-weight:var(--fw-semibold);
  color:var(--secondary); border-radius:var(--radius-md); cursor:pointer;
  transition:var(--transition); display:flex; align-items:center;
}
.tab-btn.active { background:var(--white); color:var(--primary); box-shadow:var(--shadow-sm); }
</style>

<script>
function mudarTab(nome, btn) {
  document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + nome).style.display = '';
  btn.classList.add('active');
}

let pendingDecisao = null;

function decidir(id, decisao, btnEl, pedirQuantidade = 0, unidadeSigla = '') {
  const titulo = decisao === 'aceita' ? 'Aceitar oportunidade?' : 'Rejeitar oportunidade?';
  const msg    = decisao === 'aceita'
    ? 'Ao aceitar, uma solicitacao sera criada automaticamente em seu nome. Esta acao nao pode ser desfeita.'
    : 'Ao rejeitar, esta oportunidade nao estara mais disponivel para voce.';

  document.getElementById('confirmarTitulo').textContent = titulo;
  document.getElementById('confirmarMsg').textContent    = msg;

  const bloco = document.getElementById('blocoQuantidade');
  if (decisao === 'aceita' && pedirQuantidade) {
    bloco.style.display = '';
    document.getElementById('inputQuantidade').value = '';
    document.getElementById('labelUnidade').textContent = unidadeSigla;
  } else {
    bloco.style.display = 'none';
  }

  const btnConfirmar = document.getElementById('btnConfirmarDecisao');
  btnConfirmar.className   = decisao === 'aceita' ? 'btn btn-primary' : 'btn btn-danger';
  btnConfirmar.textContent = decisao === 'aceita' ? 'Aceitar' : 'Rejeitar';

  pendingDecisao = { id, decisao, pedirQuantidade };
  document.getElementById('modalConfirmar').style.display = 'flex';
}

function fecharConfirmar() {
  document.getElementById('modalConfirmar').style.display = 'none';
  pendingDecisao = null;
}

document.getElementById('btnConfirmarDecisao').addEventListener('click', async () => {
  if (!pendingDecisao) return;
  const { id, decisao, pedirQuantidade } = pendingDecisao;

  // Valida quantidade se necessario
  if (decisao === 'aceita' && pedirQuantidade) {
    const qtd = parseFloat(document.getElementById('inputQuantidade').value);
    if (!qtd || qtd <= 0) {
      document.getElementById('inputQuantidade').style.borderColor = 'var(--brand)';
      document.getElementById('inputQuantidade').focus();
      return;
    }
    document.getElementById('inputQuantidade').style.borderColor = '';
  }

  const btn = document.getElementById('btnConfirmarDecisao');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;border-top-color:var(--white);"></span>';

  try {
    const res  = await fetch(`/api/oportunidades/index.php?id=${id}&acao=decidir`, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        decisao,
        quantidade: (pendingDecisao.pedirQuantidade && decisao === 'aceita')
          ? parseFloat(document.getElementById('inputQuantidade').value) || 0
          : null,
      }),
    });
    const data = await res.json();
    if (res.status === 403 && !data.success) {
      data.message = 'Apenas diretores e escriturários podem responder oportunidades.';
    }

    fecharConfirmar();

    if (data.success) {
      // Toast de sucesso
      const t = document.createElement('div');
      t.className = 'alert alert-success animate-slide-up';
      t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;max-width:380px;box-shadow:var(--shadow-xl);';
      t.innerHTML = `<i data-lucide="check-circle" width="16" height="16"></i> ${data.message}`;
      document.body.appendChild(t);
      if (window.lucide) lucide.createIcons();

      // Se aceitou, redireciona para a solicitação gerada
      if (decisao === 'aceita' && data.data?.id_solicitacao) {
        setTimeout(() => window.location.href = `/pages/solicitacao-detalhe.php?id=${data.data.id_solicitacao}`, 1500);
      } else {
        setTimeout(() => location.reload(), 1200);
      }
    } else {
      alert(data.message);
      btn.disabled = false;
    }
  } catch(e) {
    alert('Erro de conexão.');
    btn.disabled = false;
  }
});
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
