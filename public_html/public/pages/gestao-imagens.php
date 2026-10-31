<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::requireSuperadmin();
$pageTitle  = 'Gestão de Imagens';
$activePage = 'gestao-imagens';
require_once __DIR__ . '/../../templates/layout-header.php';
?>

<div class="animate-fade-in">
  <div class="d-flex align-center justify-between mb-6 flex-wrap gap-4">
    <div>
      <h2 class="text-h2">Gestão de Imagens</h2>
      <p class="text-body" style="color:var(--secondary);margin-top:4px;">
        Imagens anexadas a solicitações de serviço armazenadas no servidor.
      </p>
    </div>
  </div>

  <!-- Filtro -->
  <div class="card mb-6" style="padding:var(--space-4);">
    <div style="display:flex;gap:var(--space-4);align-items:flex-end;flex-wrap:wrap;">
      <div>
        <label class="form-label">Mostrar imagens mais antigas que</label>
        <div style="display:flex;align-items:center;gap:var(--space-2);">
          <input type="number" id="filtroMeses" class="form-control" value="3" min="1" max="60"
                 style="width:80px;">
          <span style="font-size:14px;color:var(--secondary);">meses</span>
        </div>
      </div>
      <button class="btn btn-primary btn-sm" onclick="carregar()">
        <i data-lucide="search" width="14" height="14"></i> Buscar
      </button>
      <button class="btn btn-outline btn-sm" onclick="carregarTodas()">
        <i data-lucide="image" width="14" height="14"></i> Mostrar todas
      </button>
    </div>
  </div>

  <!-- Acoes em massa -->
  <div id="barraAcoes" style="display:none;" class="mb-4">
    <div style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3) var(--space-4);
                background:var(--neutral-100);border-radius:var(--radius-lg);">
      <span id="labelSelecionadas" style="font-size:13px;font-weight:var(--fw-semibold);flex:1;"></span>
      <button class="btn btn-outline btn-sm" onclick="baixarSelecionadas()">
        <i data-lucide="download" width="14" height="14"></i> Baixar selecionadas
      </button>
      <button class="btn btn-sm" onclick="excluirSelecionadas()"
              style="background:var(--brand);color:var(--white);border:none;">
        <i data-lucide="trash-2" width="14" height="14"></i> Excluir selecionadas
      </button>
    </div>
  </div>

  <!-- Resultados -->
  <div id="loading" style="display:none;text-align:center;padding:var(--space-10);">
    <span class="spinner spinner-brand" style="width:28px;height:28px;"></span>
  </div>

  <div id="conteudo" style="display:none;">
    <!-- Cabecalho da tabela com selecionar todos -->
    <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-3);">
      <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;font-size:13px;color:var(--secondary);">
        <input type="checkbox" id="selecionarTodos" onchange="toggleTodos(this.checked)">
        Selecionar todas
      </label>
      <span id="totalLabel" style="font-size:13px;color:var(--secondary);"></span>
    </div>
    <div id="gradeImagens" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:var(--space-4);"></div>
  </div>

  <div id="vazio" style="display:none;">
    <div class="card" style="border-radius:var(--radius-xl);">
      <div class="empty-state" style="padding:var(--space-10);">
        <i data-lucide="image" width="40" height="40" style="color:var(--neutral-300);margin:0 auto;"></i>
        <p class="empty-state-title">Nenhuma imagem encontrada</p>
        <p class="empty-state-text">Nao ha imagens com mais de <span id="msgMeses"></span> meses armazenadas.</p>
      </div>
    </div>
  </div>
</div>

<script>
let imagensAtuais = [];

async function carregarTodas() {
  document.getElementById('filtroMeses').value = 0;
  await carregarComMeses(0);
}

async function carregar() {
  const meses = parseInt(document.getElementById('filtroMeses').value) || 3;
  await carregarComMeses(meses);
}

async function carregarComMeses(meses) {
  document.getElementById('loading').style.display  = '';
  document.getElementById('conteudo').style.display = 'none';
  document.getElementById('vazio').style.display    = 'none';
  document.getElementById('barraAcoes').style.display = 'none';

  const res  = await fetch(`/api/upload/gestao.php?meses=${meses}`);
  const data = await res.json();

  document.getElementById('loading').style.display = 'none';

  if (!data.success || !data.data.imagens.length) {
    document.getElementById('msgMeses').textContent = meses;
    document.getElementById('vazio').style.display  = '';
    return;
  }

  imagensAtuais = data.data.imagens;
  renderizar(imagensAtuais);
  document.getElementById('totalLabel').textContent = `${imagensAtuais.length} imagem(ns) encontrada(s)`;
  document.getElementById('conteudo').style.display = '';
  document.getElementById('selecionarTodos').checked = false;
  if (window.lucide) lucide.createIcons();
}

function renderizar(imagens) {
  const grade = document.getElementById('gradeImagens');
  grade.innerHTML = imagens.map(img => {
    const dt  = new Date(img.created_at.replace(' ','T'));
    const dtStr = dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
    const kb  = img.tamanho ? (img.tamanho / 1024).toFixed(0) + ' KB' : 'N/A';
    return `
    <div class="card" style="border-radius:var(--radius-xl);padding:0;overflow:hidden;" id="card-${img.id}">
      <div style="position:relative;">
        <label style="position:absolute;top:8px;left:8px;z-index:2;cursor:pointer;">
          <input type="checkbox" class="chk-imagem" data-id="${img.id}"
                 onchange="atualizarSelecao()"
                 style="width:18px;height:18px;accent-color:var(--brand);">
        </label>
        <img src="/api/upload/imagem.php?arquivo=${encodeURIComponent(img.foto_servico)}"
             alt="Foto servico"
             onclick="abrirLightbox('${encodeURIComponent(img.foto_servico)}', ${img.id})"
             style="width:100%;height:180px;object-fit:cover;display:block;cursor:zoom-in;">
      </div>
      <div style="padding:var(--space-3);">
        <div style="font-size:13px;font-weight:var(--fw-semibold);margin-bottom:2px;">
          Solicitacao #${String(img.id).padStart(4,'0')}
        </div>
        <div style="font-size:12px;color:var(--secondary);">${img.escola_nome}</div>
        <div style="font-size:12px;color:var(--secondary);">${img.diretor_nome}</div>
        <div style="display:flex;justify-content:space-between;margin-top:var(--space-2);">
          <span style="font-size:11px;color:var(--secondary);">${dtStr}</span>
          <span style="font-size:11px;color:var(--secondary);">${kb}</span>
        </div>
      </div>
    </div>`;
  }).join('');
}

function toggleTodos(checked) {
  document.querySelectorAll('.chk-imagem').forEach(c => c.checked = checked);
  atualizarSelecao();
}

function atualizarSelecao() {
  const selecionadas = getSelecionadas();
  const barra = document.getElementById('barraAcoes');
  const label = document.getElementById('labelSelecionadas');
  if (selecionadas.length > 0) {
    barra.style.display = '';
    label.textContent   = `${selecionadas.length} imagem(ns) selecionada(s)`;
  } else {
    barra.style.display = 'none';
  }
}

function getSelecionadas() {
  return Array.from(document.querySelectorAll('.chk-imagem:checked')).map(c => parseInt(c.dataset.id));
}

function baixarSelecionadas() {
  const ids = getSelecionadas();
  ids.forEach(id => {
    const img = imagensAtuais.find(i => i.id === id);
    if (!img) return;
    const a = document.createElement('a');
    a.href     = `/api/upload/imagem.php?arquivo=${encodeURIComponent(img.foto_servico)}`;
    a.download = img.foto_servico;
    a.target   = '_blank';
    a.click();
  });
}

async function excluirSelecionadas() {
  const ids = getSelecionadas();
  if (!ids.length) return;
  if (!confirm(`Excluir ${ids.length} imagem(ns)? Esta acao nao pode ser desfeita.`)) return;

  const res  = await fetch('/api/upload/gestao.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids }),
  });
  const data = await res.json();

  if (data.success) {
    // Remove cards da tela
    ids.forEach(id => document.getElementById(`card-${id}`)?.remove());
    imagensAtuais = imagensAtuais.filter(i => !ids.includes(i.id));
    document.getElementById('totalLabel').textContent = `${imagensAtuais.length} imagem(ns) encontrada(s)`;
    document.getElementById('barraAcoes').style.display = 'none';
    document.getElementById('selecionarTodos').checked  = false;
    if (!imagensAtuais.length) {
      document.getElementById('conteudo').style.display = 'none';
      document.getElementById('msgMeses').textContent = document.getElementById('filtroMeses').value;
      document.getElementById('vazio').style.display  = '';
    }
    alert(data.message);
  } else {
    alert(data.message || 'Erro ao excluir imagens.');
  }
}

// ===== Lightbox =====
function abrirLightbox(arquivo, id) {
  let ov = document.getElementById('imgLightbox');
  if (!ov) {
    ov = document.createElement('div');
    ov.id = 'imgLightbox';
    ov.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);'
      + 'display:flex;align-items:center;justify-content:center;padding:24px;';
    ov.onclick = (e) => { if (e.target === ov) fecharLightbox(); };
    ov.innerHTML = `
      <button onclick="fecharLightbox()" aria-label="Fechar"
              style="position:absolute;top:16px;right:20px;width:40px;height:40px;border:none;
                     border-radius:50%;background:rgba(255,255,255,0.15);color:#fff;font-size:22px;
                     cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
      <div id="lightboxLegenda"
           style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);color:#fff;
                  font-size:13px;background:rgba(0,0,0,0.5);padding:6px 14px;border-radius:50px;"></div>
      <img id="lightboxImg" alt="Foto ampliada"
           style="max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;
                  box-shadow:0 8px 40px rgba(0,0,0,0.5);">`;
    document.body.appendChild(ov);
  }
  document.getElementById('lightboxImg').src = '/api/upload/imagem.php?arquivo=' + arquivo;
  document.getElementById('lightboxLegenda').textContent = 'Solicitacao #' + String(id).padStart(4, '0');
  ov.style.display = 'flex';
}

function fecharLightbox() {
  const ov = document.getElementById('imgLightbox');
  if (ov) ov.style.display = 'none';
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') fecharLightbox();
});

carregar();
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
