<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Repositories\SugestaoRepository;
use App\Helpers\Session;

AuthMiddleware::requireDiretor();

$pageTitle  = 'Sugerir Produto';
$activePage = 'sugerir';
$idDiretor  = Session::getUsuarioId();
$idEscola   = Session::getIdEscola();

$repo       = new SugestaoRepository();
$minhas     = $repo->listar(null);
// Filtra apenas as do diretor logado
$minhas = array_filter($minhas, fn($s) => (int)$s['escola_nome'] || true); // todas da escola

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="mb-8">
    <h2 class="text-h2">Sugerir Novo Produto</h2>
    <p class="text-body" style="color:var(--secondary);margin-top:4px;">
      Informe um produto que não está na lista e que sua escola precisa.
    </p>
  </div>

  <div class="grid-2" style="gap:var(--space-6);align-items:start;">

    <!-- Formulário -->
    <div class="card" style="border-radius:var(--radius-xl);">
      <div class="card-header">
        <h3 class="card-title">Nova Sugestão</h3>
      </div>

      <div id="formAlert" style="display:none;" class="mb-4"></div>

      <div class="form-group">
        <label class="form-label">
          Descrição do produto necessário <span class="required">*</span>
        </label>
        <textarea id="descricao" class="form-control" rows="5"
                  placeholder="Descreva o produto que sua escola necessita. Inclua informações como nome, especificações, finalidade de uso..."
                  maxlength="400"
                  data-maxlength="400" data-counter="cntDesc"></textarea>
        <div class="char-counter" id="cntDesc">0/400</div>
        <div class="form-hint" style="margin-top:var(--space-2);">
          Seja específico para facilitar o cadastro pelo administrador.
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Link de referencia</label>
        <input type="url" id="linkReferencia" class="form-control"
               placeholder="https://www.exemplo.com.br/produto"
               oninput="previewLink(this.value)">
        <div id="linkPreview" style="display:none;margin-top:var(--space-2);"></div>
        <div class="form-hint" style="margin-top:var(--space-2);">
          Envie um link de referencia para que a equipe de compras esteja alinhada com a sua expectativa em relacao ao produto a ser recebido. Quanto mais especifico, melhor sera o resultado.
        </div>
      </div>

      <button class="btn btn-primary w-100" id="btnEnviar" onclick="enviarSugestao()">
        <i data-lucide="send" width="16" height="16"></i>
        Enviar sugestao
      </button>
    </div>

    <!-- Dicas -->
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
      <div class="card-flat" style="border-radius:var(--radius-xl);padding:var(--space-5);">
        <div style="display:flex;gap:var(--space-3);align-items:flex-start;">
          <div class="stat-icon stat-icon-brand" style="width:36px;height:36px;flex-shrink:0;">
            <i data-lucide="lightbulb" width="18" height="18"></i>
          </div>
          <div>
            <div style="font-weight:var(--fw-semibold);font-size:14px;margin-bottom:4px;">Como funciona?</div>
            <p class="text-body" style="color:var(--secondary);font-size:13px;">
              Sua sugestão será enviada para o administrador do sistema, que poderá cadastrar o novo produto na lista oficial.
            </p>
          </div>
        </div>
      </div>

      <div class="card-flat" style="border-radius:var(--radius-xl);padding:var(--space-5);">
        <div style="display:flex;gap:var(--space-3);align-items:flex-start;">
          <div class="stat-icon stat-icon-green" style="width:36px;height:36px;flex-shrink:0;">
            <i data-lucide="check-circle" width="18" height="18"></i>
          </div>
          <div>
            <div style="font-weight:var(--fw-semibold);font-size:14px;margin-bottom:4px;">Boas práticas</div>
            <ul style="font-size:13px;color:var(--secondary);display:flex;flex-direction:column;gap:4px;">
              <li>• Inclua o nome comercial do produto</li>
              <li>• Mencione a quantidade aproximada necessária</li>
              <li>• Descreva a finalidade do uso</li>
              <li>• Verifique antes se o produto já existe na lista</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
const siteLabels = {
  'mercadolivre.com.br': ['Mercado Livre', '🛒'],
  'mercadolibre.com.br': ['Mercado Livre', '🛒'],
  'amazon.com.br':       ['Amazon',        '📦'],
  'shopee.com.br':       ['Shopee',        '🏷️'],
  'magazineluiza.com.br':['Magazine Luiza','🏪'],
  'americanas.com.br':   ['Americanas',    '🏪'],
  'casasbahia.com.br':   ['Casas Bahia',   '🏪'],
};

function previewLink(url) {
  const preview = document.getElementById('linkPreview');
  if (!url) { preview.style.display = 'none'; return; }
  try {
    const host = new URL(url).hostname.replace(/^www\./, '');
    let nome = host, emoji = '🔗';
    for (const [domain, info] of Object.entries(siteLabels)) {
      if (host.includes(domain)) { [nome, emoji] = info; break; }
    }
    preview.style.display = '';
    preview.innerHTML = `
      <a href="${url}" target="_blank" rel="noopener"
         style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;
                border-radius:50px;background:var(--neutral-100);border:1px solid var(--neutral-200);
                font-size:12px;font-weight:500;color:var(--brand);text-decoration:none;">
        <span>${emoji}</span> ${nome} <span style="opacity:0.6;font-size:10px;">↗</span>
      </a>`;
  } catch(e) { preview.style.display = 'none'; }
}

async function enviarSugestao() {
  const descricao = document.getElementById('descricao').value.trim();
  const link      = document.getElementById('linkReferencia').value.trim();
  document.getElementById('formAlert').style.display = 'none';

  if (!descricao) {
    mostrarAlerta('A descrição é obrigatória.', 'danger');
    return;
  }

  const btn = document.getElementById('btnEnviar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span> Enviando...';

  try {
    const res  = await fetch('/api/sugestoes/index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ descricao, link }),
    });
    const data = await res.json();

    if (data.success) {
      mostrarAlerta(data.message, 'success');
      document.getElementById('descricao').value    = '';
      document.getElementById('linkReferencia').value = '';
      document.getElementById('descricao').dispatchEvent(new Event('input'));
    } else {
      mostrarAlerta(data.message, 'danger');
    }
  } catch (e) {
    mostrarAlerta('Erro de conexão. Tente novamente.', 'danger');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="send" width="16" height="16"></i> Enviar sugestão';
    if (window.lucide) lucide.createIcons();
  }
}

function mostrarAlerta(msg, tipo) {
  const el = document.getElementById('formAlert');
  el.className = `alert alert-${tipo} animate-slide-up`;
  el.innerHTML = `<i data-lucide="${tipo === 'success' ? 'check-circle' : 'alert-circle'}" width="16" height="16"></i> ${msg}`;
  el.style.display = 'flex';
  if (window.lucide) lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
