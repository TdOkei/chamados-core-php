<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::requireDiretor();

$pageTitle  = 'Confirmar Entrega';
$activePage = '';

$tokenUrl = trim($_GET['token'] ?? '');

require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">

  <div class="mb-6">
    <h2 class="text-h2">Confirmar Entrega</h2>
    <p class="text-body" style="color:var(--secondary);margin-top:4px;">
      Escaneie o QR Code do pedido ou insira o token manualmente.
    </p>
  </div>

  <!-- FASE 1: Escaneador -->
  <div id="faseEscanear">
    <div class="card" style="border-radius:var(--radius-xl);overflow:hidden;padding:0;">
      <div id="readerArea" style="position:relative;background:var(--black);min-height:280px;display:flex;align-items:center;justify-content:center;">
        <video id="videoCamera" autoplay playsinline muted style="width:100%;max-height:360px;object-fit:cover;display:none;"></video>

        <!-- Mira -->
        <div id="miraOverlay" style="position:absolute;inset:0;display:none;align-items:center;justify-content:center;pointer-events:none;">
          <div style="position:relative;">
            <div style="width:200px;height:200px;border:3px solid var(--brand);border-radius:var(--radius-lg);box-shadow:0 0 0 9999px rgba(0,0,0,0.5);"></div>
            <div style="position:absolute;top:-3px;left:-3px;width:22px;height:22px;border-top:4px solid #fff;border-left:4px solid #fff;border-radius:4px 0 0 0;"></div>
            <div style="position:absolute;top:-3px;right:-3px;width:22px;height:22px;border-top:4px solid #fff;border-right:4px solid #fff;border-radius:0 4px 0 0;"></div>
            <div style="position:absolute;bottom:-3px;left:-3px;width:22px;height:22px;border-bottom:4px solid #fff;border-left:4px solid #fff;border-radius:0 0 0 4px;"></div>
            <div style="position:absolute;bottom:-3px;right:-3px;width:22px;height:22px;border-bottom:4px solid #fff;border-right:4px solid #fff;border-radius:0 0 4px 0;"></div>
            <div style="position:absolute;left:0;right:0;height:2px;background:linear-gradient(to right,transparent,var(--brand),transparent);animation:scan 2s linear infinite;top:0;"></div>
          </div>
        </div>

        <!-- Estado inicial -->
        <div id="estadoInicial" style="display:flex;flex-direction:column;align-items:center;gap:var(--space-4);padding:var(--space-10);text-align:center;">
          <div style="width:72px;height:72px;border-radius:var(--radius-xl);background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;">
            <i data-lucide="qr-code" width="36" height="36" style="color:rgba(255,255,255,0.5);"></i>
          </div>
          <div style="color:rgba(255,255,255,0.85);font-family:var(--font-primary);font-size:16px;font-weight:var(--fw-semibold);">Pronto para escanear</div>
          <p style="color:rgba(255,255,255,0.45);font-size:13px;max-width:240px;">Ative a câmera e aponte para o QR Code do pedido.</p>
        </div>

        <!-- Estado processando -->
        <div id="estadoProcessando" style="display:none;flex-direction:column;align-items:center;gap:var(--space-4);padding:var(--space-10);">
          <span class="spinner" style="width:40px;height:40px;border-width:3px;border-color:rgba(255,255,255,0.2);border-top-color:var(--brand);"></span>
          <div style="color:rgba(255,255,255,0.8);font-size:14px;">Processando...</div>
        </div>
      </div>

      <!-- Botões de câmera -->
      <div style="padding:var(--space-5);display:flex;flex-direction:column;gap:var(--space-3);">
        <button class="btn btn-primary w-100" id="btnAtivarCamera" onclick="ativarCamera()" style="height:48px;">
          <i data-lucide="camera" width="16" height="16"></i> Ativar câmera
        </button>
        <button class="btn btn-outline w-100" id="btnPararCamera" onclick="pararCamera()" style="display:none;height:48px;">
          <i data-lucide="camera-off" width="16" height="16"></i> Parar câmera
        </button>
      </div>
    </div>

    <!-- Divisor -->
    <div style="display:flex;align-items:center;gap:var(--space-3);margin:var(--space-5) 0;">
      <div style="flex:1;height:1px;background:var(--neutral-200);"></div>
      <span style="font-size:12px;color:var(--secondary);font-weight:var(--fw-semibold);">OU</span>
      <div style="flex:1;height:1px;background:var(--neutral-200);"></div>
    </div>

    <!-- Token manual -->
    <div class="card-flat" style="border-radius:var(--radius-xl);">
      <label class="form-label">Inserir token manualmente</label>
      <div style="display:flex;gap:var(--space-3);">
        <input type="text" id="tokenManual" class="form-control"
               placeholder="Cole o token aqui..." style="font-family:monospace;font-size:13px;" maxlength="64">
        <button class="btn btn-primary" onclick="processarTokenManual()" style="flex-shrink:0;">
          <i data-lucide="arrow-right" width="16" height="16"></i>
        </button>
      </div>
    </div>

    <div id="alertaFase1" style="display:none;" class="mt-4"></div>
  </div>

  <!-- FASE 2A: Destino errado -->
  <div id="faseDestinoErrado" style="display:none;">
    <div class="card" style="border-radius:var(--radius-xl);border:2.5px solid var(--brand);">
      <div style="text-align:center;padding:var(--space-6) var(--space-4) 0;">
        <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-4);">
          <i data-lucide="alert-triangle" width="32" height="32" style="color:var(--brand);"></i>
        </div>
        <h3 style="font-family:var(--font-primary);font-size:20px;font-weight:var(--fw-bold);color:var(--brand);margin-bottom:var(--space-2);">Pedido de outro destino</h3>
        <p style="font-size:14px;color:var(--secondary);margin-bottom:var(--space-4);">
          Este QR Code pertence a um pedido destinado a outra escola.<br>
          <strong id="escolaDestinoNome" style="color:var(--primary);"></strong>
        </p>
      </div>
      <div style="padding:var(--space-4) var(--space-6) var(--space-6);">
        <div class="alert alert-warning mb-4" style="font-size:13px;">
          <i data-lucide="info" width="14" height="14"></i>
          Ao confirmar, o pedido será marcado como <strong>Retornado</strong> e voltará para a fila para ser corrigido.
        </div>
        <div style="display:flex;flex-direction:column;gap:var(--space-3);">
          <button class="btn btn-danger w-100" id="btnConfirmarRetorno" onclick="confirmarRetorno()" style="height:48px;">
            <i data-lucide="undo-2" width="16" height="16"></i> Confirmar — Pedido retornado
          </button>
          <button class="btn btn-outline w-100" onclick="reiniciar()" style="height:44px;">
            <i data-lucide="arrow-left" width="16" height="16"></i> Voltar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- FASE 2B: QR válido -->
  <div id="faseConfirmacao" style="display:none;">
    <div class="card" style="border-radius:var(--radius-xl);border:2.5px solid #16a34a;">
      <div style="text-align:center;padding:var(--space-6) var(--space-4) 0;">
        <div style="width:56px;height:56px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-3);">
          <i data-lucide="check-circle" width="28" height="28" style="color:#16a34a;"></i>
        </div>
        <h3 style="font-family:var(--font-primary);font-size:18px;font-weight:var(--fw-bold);color:#14532d;margin-bottom:4px;">QR Code válido!</h3>
        <p style="font-size:13px;color:var(--secondary);margin-bottom:0;">Destinado a: <strong id="escolaConfirmNome" style="color:var(--primary);"></strong></p>
      </div>

      <div class="divider" style="margin:var(--space-5) 0 0;"></div>

      <div style="padding:var(--space-5);">

        <!-- Checkbox item faltando -->
        <label class="form-check" style="padding:var(--space-4);background:var(--neutral-100);border-radius:var(--radius-lg);cursor:pointer;margin-bottom:var(--space-4);">
          <input type="checkbox" class="form-check-input" id="checkItemFaltando" onchange="toggleItemFaltando()">
          <div>
            <div class="form-check-label" style="font-weight:var(--fw-semibold);">Ha algum item faltando no pedido</div>
            <div style="font-size:12px;color:var(--secondary);margin-top:2px;">Marque se recebeu o pedido com itens faltantes</div>
          </div>
        </label>

        <!-- Lista de itens (visivel apenas quando checkbox marcado e categoria=produto) -->
        <div id="blocoItensFaltando" style="display:none;margin-bottom:var(--space-4);">
          <div style="font-size:13px;font-weight:var(--fw-semibold);margin-bottom:var(--space-3);color:var(--primary);">
            Marque os itens que estao faltando:
          </div>
          <div id="listaItens" style="display:flex;flex-direction:column;gap:var(--space-2);"></div>
          <div id="alertaItens" style="display:none;margin-top:var(--space-3);"></div>
        </div>

        <!-- Nome de quem recebeu -->
        <div class="form-group" id="blocoNomeRecebedor">
          <label class="form-label">Nome de quem recebeu <span class="required">*</span></label>
          <textarea id="obsConfirmacao" class="form-control" rows="3"
                    placeholder="Nome completo de quem esta recebendo..."
                    maxlength="150" data-maxlength="150" data-counter="cntObs"></textarea>
          <div class="char-counter" id="cntObs">0/150</div>
          <div class="form-hint">Este nome ficara registrado como confirmacao da entrega.</div>
        </div>

        <div class="form-group" id="blocoFotoConclusao" style="display:none;">
          <label class="form-label">Foto do serviço concluído <span class="required">*</span></label>
          <input type="file" id="inputFotoConclusao" accept="image/*" capture="environment" class="form-control">
          <div class="form-hint">Fotografe o serviço finalizado. Esta foto fica registrada no histórico do pedido.</div>
        </div>

        <div id="alertaConfirmacao" style="display:none;" class="mb-4"></div>

        <button class="btn btn-primary w-100" id="btnConfirmarEntrega" onclick="confirmarEntrega()" style="height:48px;font-size:15px;">
          <i data-lucide="check" width="16" height="16"></i> Confirmar recebimento
        </button>
      </div>
    </div>
  </div>

  <!-- FASE 3: Resultado -->
  <div id="faseResultado" style="display:none;">
    <div class="card" id="cardResultado" style="border-radius:var(--radius-xl);text-align:center;padding:var(--space-10);">
      <div id="iconeResultado" style="width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-5);"></div>
      <h3 id="tituloResultado" style="font-family:var(--font-primary);font-size:22px;font-weight:var(--fw-bold);margin-bottom:var(--space-3);"></h3>
      <p id="msgResultado" style="font-size:14px;color:var(--secondary);margin-bottom:var(--space-6);line-height:1.6;"></p>
      <a href="/pages/solicitacoes.php" class="btn btn-primary w-100" style="height:48px;font-size:15px;">
        <i data-lucide="list" width="16" height="16"></i> Ver minhas solicitações
      </a>
    </div>
  </div>

</div>

<style>
@keyframes scan {
  0%   { top: 0; }
  50%  { top: calc(100% - 2px); }
  100% { top: 0; }
}
</style>

<script src="/assets/js/vendor/jsQR.js"></script>
<script src="/assets/js/img-resize.js"></script>
<script>
let videoStream = null;
let scanInterval = null;
let tokenAtual   = null;
let processando  = false;

<?php if ($tokenUrl): ?>
window.addEventListener('DOMContentLoaded', () => processarToken('<?= htmlspecialchars($tokenUrl, ENT_QUOTES) ?>'));
<?php endif; ?>

async function ativarCamera() {
  const btn = document.getElementById('btnAtivarCamera');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span> Ativando...';

  try {
    videoStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment', width: { ideal: 1280 } }
    });
    const video = document.getElementById('videoCamera');
    video.srcObject = videoStream;
    video.style.display = 'block';

    document.getElementById('estadoInicial').style.display   = 'none';
    document.getElementById('miraOverlay').style.display     = 'flex';
    document.getElementById('btnAtivarCamera').style.display = 'none';
    document.getElementById('btnPararCamera').style.display  = '';

    scanInterval = setInterval(escanearFrame, 200);
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="camera" width="16" height="16"></i> Ativar câmera';
    if (window.lucide) lucide.createIcons();
    let msg = 'Não foi possível acessar a câmera.';
    if (err.name === 'NotAllowedError') msg = 'Permissão de câmera negada. Autorize nas configurações.';
    if (err.name === 'NotFoundError')   msg = 'Nenhuma câmera encontrada.';
    mostrarAlerta('alertaFase1', msg, 'danger');
  }
}

function pararCamera() {
  if (scanInterval) clearInterval(scanInterval);
  if (videoStream)  videoStream.getTracks().forEach(t => t.stop());
  const video = document.getElementById('videoCamera');
  video.srcObject = null;
  video.style.display = 'none';
  document.getElementById('miraOverlay').style.display      = 'none';
  document.getElementById('estadoInicial').style.display    = 'flex';
  document.getElementById('btnPararCamera').style.display   = 'none';
  document.getElementById('btnAtivarCamera').style.display  = '';
  document.getElementById('btnAtivarCamera').disabled       = false;
  document.getElementById('btnAtivarCamera').innerHTML      = '<i data-lucide="camera" width="16" height="16"></i> Ativar câmera';
  if (window.lucide) lucide.createIcons();
}

function escanearFrame() {
  if (processando) return;
  const video = document.getElementById('videoCamera');
  if (!video.videoWidth) return;

  const canvas = document.createElement('canvas');
  canvas.width  = video.videoWidth;
  canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);

  const img  = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
  const code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });

  if (code?.data) {
    processando = true;
    pararCamera();
    let token = code.data;
    try {
      const url   = new URL(code.data);
      const param = url.searchParams.get('token');
      if (param) token = param;
    } catch (_) {}
    processarToken(token);
  }
}

function processarTokenManual() {
  const token = document.getElementById('tokenManual').value.trim();
  if (!token) { mostrarAlerta('alertaFase1', 'Cole o token no campo.', 'warning'); return; }
  processarToken(token);
}

async function processarToken(token) {
  processando = true;
  tokenAtual  = token;
  document.getElementById('estadoInicial').style.display     = 'none';
  document.getElementById('estadoProcessando').style.display = 'flex';

  try {
    const res  = await fetch('/api/qrcode/index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'validar', token }),
    });
    const data = await res.json();

    document.getElementById('estadoProcessando').style.display = 'none';
    document.getElementById('estadoInicial').style.display     = 'flex';

    if (!data.success) {
      processando = false;
      mostrarAlerta('alertaFase1', data.message, 'danger');
      return;
    }

    if (data.data.cenario === 'destino_errado') {
      document.getElementById('escolaDestinoNome').textContent = data.data.escola_destino || 'outra escola';
      mostrarFase('faseDestinoErrado');
    } else {
      document.getElementById('escolaConfirmNome').textContent = data.data.escola_nome || '';
      document.getElementById('checkItemFaltando').checked = false;
      document.getElementById('obsConfirmacao').value = '';
      itensAtuais = data.data.itens || [];
      exigeFotoConclusao = !!data.data.exige_foto_conclusao;
      document.getElementById('blocoFotoConclusao').style.display = exigeFotoConclusao ? '' : 'none';
      document.getElementById('inputFotoConclusao').value = '';
      itensParcialmenteEntregues = data.data.itens_ja_entregues || [];
      renderizarItens();
      toggleItemFaltando();
      document.getElementById('obsConfirmacao').dispatchEvent(new Event('input'));
      mostrarFase('faseConfirmacao');
    }
  } catch (e) {
    document.getElementById('estadoProcessando').style.display = 'none';
    document.getElementById('estadoInicial').style.display     = 'flex';
    processando = false;
    mostrarAlerta('alertaFase1', 'Erro de conexão. Tente novamente.', 'danger');
  }
}

async function confirmarRetorno() {
  const btn = document.getElementById('btnConfirmarRetorno');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';
  await enviarResultado('retornado', '');
}

let itensAtuais = [];
let itensParcialmenteEntregues = [];
let exigeFotoConclusao = false;

function renderizarItens() {
  const lista = document.getElementById('listaItens');
  if (!lista) return;
  lista.innerHTML = '';

  // Filtra itens que ainda nao foram entregues
  const itensPendentes = itensAtuais.filter(item => !itensParcialmenteEntregues.includes(parseInt(item.id)));

  if (itensPendentes.length === 0) {
    lista.innerHTML = '<p style="font-size:13px;color:var(--secondary);text-align:center;padding:var(--space-3);">Todos os itens ja foram entregues anteriormente.</p>';
    return;
  }

  itensPendentes.forEach(item => {
    const qtd = parseFloat(item.quantidade).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const div = document.createElement('label');
    div.style.cssText = 'display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3);background:var(--neutral-100);border-radius:var(--radius-md);cursor:pointer;border:2px solid transparent;transition:var(--transition);';
    div.innerHTML = `
      <input type="checkbox" data-item-id="${item.id}" class="form-check-input" style="width:20px;height:20px;flex-shrink:0;"
             onchange="this.closest('label').style.borderColor=this.checked?'var(--brand)':'transparent';">
      <div style="flex:1;">
        <div style="font-size:14px;font-weight:var(--fw-medium);">${item.produto_nome}${item.detalhes ? ' <span style="color:var(--secondary);font-weight:400;">— ' + String(item.detalhes).replace(/[&<>"']/g, c => '&#' + c.charCodeAt(0) + ';') + '</span>' : ''}</div>
        <div style="font-size:12px;color:var(--secondary);">${qtd} ${item.unidade_sigla}</div>
      </div>
      <i data-lucide="package-x" width="16" height="16" style="color:var(--secondary);flex-shrink:0;"></i>
    `;
    lista.appendChild(div);
  });

  if (window.lucide) lucide.createIcons();
}

function toggleItemFaltando() {
  const checked  = document.getElementById('checkItemFaltando').checked;
  const temItens = itensAtuais.length > 0;

  // Mostra lista de itens se for produto e checkbox marcado
  document.getElementById('blocoItensFaltando').style.display = (checked && temItens) ? '' : 'none';

  // Campo nome: sempre visivel, independente do cenario
  document.getElementById('blocoNomeRecebedor').style.display = '';

  const btn = document.getElementById('btnConfirmarEntrega');
  if (checked) {
    btn.className = 'btn btn-outline w-100';
    btn.style.cssText = 'height:48px;font-size:15px;border-color:var(--brand);color:var(--brand);';
    btn.innerHTML = '<i data-lucide="package-x" width="16" height="16"></i> Confirmar — item em falta';
  } else {
    btn.className = 'btn btn-primary w-100';
    btn.style.cssText = 'height:48px;font-size:15px;';
    btn.innerHTML = '<i data-lucide="check" width="16" height="16"></i> Confirmar recebimento';
  }
  document.getElementById('obsConfirmacao').value = '';
  document.getElementById('obsConfirmacao').dispatchEvent(new Event('input'));
  if (window.lucide) lucide.createIcons();
}

async function confirmarEntrega() {
  const itemFaltando = document.getElementById('checkItemFaltando').checked;
  const temItens     = itensAtuais.length > 0;
  document.getElementById('alertaConfirmacao').style.display = 'none';

  if (itemFaltando && temItens) {
    // Coleta checkboxes marcados (itens faltando)
    const checkboxes   = document.querySelectorAll('#listaItens input[type=checkbox]');
    const itensFaltando  = [];
    const itensEntregues = [];
    checkboxes.forEach(cb => {
      const id = parseInt(cb.dataset.itemId);
      if (cb.checked) itensFaltando.push(id);
      else itensEntregues.push(id);
    });

    if (itensFaltando.length === 0) {
      mostrarAlerta('alertaConfirmacao', 'Marque ao menos um item faltante ou desmarque a opcao acima.', 'danger');
      return;
    }

    const nomeRecebedor = document.getElementById('obsConfirmacao').value.trim();
    if (!nomeRecebedor) {
      mostrarAlerta('alertaConfirmacao', 'Informe o nome de quem recebeu os itens.', 'danger');
      return;
    }

    const btn = document.getElementById('btnConfirmarEntrega');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--brand);"></span>';
    await enviarResultado('item_em_falta', nomeRecebedor, itensFaltando, itensEntregues);

  } else {
    const obs = document.getElementById('obsConfirmacao').value.trim();
    if (!obs) {
      mostrarAlerta('alertaConfirmacao', 'Informe o nome de quem recebeu.', 'danger');
      return;
    }
    const btn = document.getElementById('btnConfirmarEntrega');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;border-top-color:var(--white);"></span>';
    let foto = null;
    if (exigeFotoConclusao) {
      foto = document.getElementById('inputFotoConclusao').files[0] || null;
      if (!foto) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="check" width="16" height="16"></i> Confirmar recebimento';
        if (window.lucide) lucide.createIcons();
        mostrarAlerta('alertaConfirmacao', 'Envie a foto do serviço concluído.', 'danger');
        return;
      }
    }
    await enviarResultado('concluido', obs, [], [], foto);
  }
}

async function enviarResultado(cenario, observacao, itensFaltando = [], itensEntregues = [], foto = null) {
  try {
    const corpo = { acao: 'processar', token: tokenAtual, cenario, observacao, itens_faltando: itensFaltando, itens_entregues: itensEntregues };
    let init;
    if (foto) {
      const fd = new FormData();
      fd.append('payload', JSON.stringify(corpo));
      fd.append('foto', await tdReduzirImagem(foto), 'foto.jpg');
      init = { method: 'POST', body: fd };
    } else {
      init = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(corpo) };
    }
    const res  = await fetch('/api/qrcode/index.php', init);
    const data = await res.json();

    if (data.success) {
      const configs = {
        'concluido':     { bg:'#dcfce7', icon:'check-circle',  color:'#16a34a', titulo:'Entrega confirmada!',          border:'#16a34a' },
        'retornado':     { bg:'#fee2e2', icon:'undo-2',        color:'var(--brand)', titulo:'Pedido retornado',         border:'var(--brand)' },
        'item_em_falta': { bg:'#ffedd5', icon:'package-x',     color:'#9a3412', titulo:'Item em falta registrado',     border:'#f97316' },
      };
      const cfg = configs[cenario] || configs['concluido'];
      document.getElementById('iconeResultado').style.background = cfg.bg;
      document.getElementById('iconeResultado').innerHTML = `<i data-lucide="${cfg.icon}" width="36" height="36" style="color:${cfg.color};"></i>`;
      document.getElementById('tituloResultado').textContent = cfg.titulo;
      document.getElementById('tituloResultado').style.color = cfg.color;
      document.getElementById('msgResultado').textContent    = data.message;
      document.getElementById('cardResultado').style.borderTop = `4px solid ${cfg.border}`;
      mostrarFase('faseResultado');
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if (window.lucide) lucide.createIcons();
    } else {
      mostrarFase(cenario === 'retornado' ? 'faseDestinoErrado' : 'faseConfirmacao');
      mostrarAlerta(cenario === 'retornado' ? 'alertaFase1' : 'alertaConfirmacao', data.message, 'danger');
      const btn = document.getElementById(cenario === 'retornado' ? 'btnConfirmarRetorno' : 'btnConfirmarEntrega');
      if (btn) { btn.disabled = false; }
    }
  } catch(e) {
    mostrarAlerta('alertaConfirmacao', 'Erro de conexão.', 'danger');
  }
}

function mostrarFase(id) {
  ['faseEscanear','faseDestinoErrado','faseConfirmacao','faseResultado'].forEach(f => {
    document.getElementById(f).style.display = f === id ? '' : 'none';
  });
}

function reiniciar() {
  processando = false;
  tokenAtual  = null;
  mostrarFase('faseEscanear');
}

function mostrarAlerta(id, msg, tipo) {
  const el = document.getElementById(id);
  if (!el) return;
  el.className = `alert alert-${tipo} animate-slide-up`;
  el.innerHTML = `<i data-lucide="${tipo==='danger'?'alert-circle':tipo==='warning'?'alert-triangle':'info'}" width="16" height="16"></i> ${msg}`;
  el.style.display = 'flex';
  if (window.lucide) lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
