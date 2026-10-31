    </main>
  </div><!-- /.main-content -->
</div><!-- /.app-shell -->

<!-- ======== DROPDOWN NOTIFICAÇÕES ======== -->
<div id="notifPanel" style="
  display:none;
  position:fixed;
  top: calc(var(--header-h) + 8px);
  right: var(--space-4);
  width: 340px;
  background: var(--white);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  z-index: 200;
  border: 1px solid var(--neutral-200);
  animation: slideDown 0.2s ease;
  overflow: hidden;
">
  <div style="padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--neutral-200); display:flex; justify-content:space-between; align-items:center;">
    <span style="font-family:var(--font-primary); font-weight:var(--fw-semibold); font-size:15px;">Notificações</span>
    <button onclick="marcarTodasLidas()" class="btn btn-ghost btn-sm" style="font-size:12px;">Marcar todas como lidas</button>
  </div>
  <div id="notifList" style="max-height: 380px; overflow-y:auto;">
    <div class="empty-state" style="padding: var(--space-8);">
      <i data-lucide="bell-off" width="32" height="32" style="color:var(--neutral-300)"></i>
      <span style="font-size:13px; color:var(--secondary);">Nenhuma notificação</span>
    </div>
  </div>
</div>

<!-- ======== SCRIPTS BASE ======== -->
<script>
// Inicializa ícones Lucide
document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();
});

// ---- Sidebar mobile ----
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const btnMenu        = document.getElementById('btnMenu');

btnMenu?.addEventListener('click', () => {
  sidebar.classList.toggle('open');
  sidebarOverlay.classList.toggle('show');
});
sidebarOverlay?.addEventListener('click', () => {
  sidebar.classList.remove('open');
  sidebarOverlay.classList.remove('show');
});

// ---- Notificações ----
const btnNotif   = document.getElementById('btnNotif');
const notifPanel = document.getElementById('notifPanel');

btnNotif?.addEventListener('click', (e) => {
  e.stopPropagation();
  const open = notifPanel.style.display === 'block';
  notifPanel.style.display = open ? 'none' : 'block';
  if (!open) carregarNotificacoes();
});

document.addEventListener('click', (e) => {
  if (!notifPanel.contains(e.target) && e.target !== btnNotif) {
    notifPanel.style.display = 'none';
  }
});

async function carregarNotificacoes() {
  try {
    const res  = await fetch('/api/notificacoes/listar.php');
    const data = await res.json();
    if (!data.success) return;

    const count   = document.getElementById('notifCount');
    const list    = document.getElementById('notifList');
    const notifs  = data.data.notificacoes || [];
    const naoLidas = data.data.nao_lidas || 0;

    // Atualiza badge
    if (naoLidas > 0) {
      count.textContent = naoLidas > 99 ? '99+' : naoLidas;
      count.style.display = 'flex';
    } else {
      count.style.display = 'none';
    }

    // Atualiza badges da nav
    atualizarBadgesNav(data.data.badges || {});

    if (notifs.length === 0) {
      list.innerHTML = `
        <div class="empty-state" style="padding: var(--space-8);">
          <i data-lucide="bell-off" width="32" height="32" style="color:var(--neutral-300)"></i>
          <span style="font-size:13px; color:var(--secondary);">Nenhuma notificação</span>
        </div>`;
      if (window.lucide) lucide.createIcons();
      return;
    }

    list.innerHTML = notifs.map(n => `
      <div class="notif-item" data-id="${n.id}" onclick="lerNotificacao(${n.id}, '${n.ref_tipo}', ${n.id_ref})"
           style="padding: var(--space-3) var(--space-5); border-bottom: 1px solid var(--neutral-100);
                  cursor:pointer; background: ${n.lida ? 'transparent' : 'var(--brand-light)'};
                  transition: var(--transition);">
        <div style="font-size:13px; font-weight: ${n.lida ? 'var(--fw-regular)' : 'var(--fw-semibold)'}; color:var(--primary); margin-bottom:3px;">
          ${escapeHtml(n.titulo)}
        </div>
        <div style="font-size:12px; color:var(--secondary);">${escapeHtml(n.mensagem)}</div>
        <div style="font-size:11px; color:var(--neutral-300); margin-top:4px;">${n.tempo_relativo}</div>
      </div>
    `).join('');

  } catch (e) { console.error('Erro ao carregar notificações:', e); }
}

async function lerNotificacao(id, tipo, idRef) {
  await fetch('/api/notificacoes/marcar-lida.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  });
  notifPanel.style.display = 'none';

  // Redireciona conforme tipo
  const rotas = {
    'solicitacao': `/pages/solicitacao-detalhe.php?id=${idRef}`,
    'oportunidade': `/pages/oportunidades.php`,
    'sugestao': `/pages/sugestoes.php`,
  };
  if (rotas[tipo]) window.location.href = rotas[tipo];
}

async function marcarTodasLidas() {
  await fetch('/api/notificacoes/marcar-todas-lidas.php', { method: 'POST' });
  carregarNotificacoes();
}

function atualizarBadgesNav(badges) {
  Object.entries(badges).forEach(([page, count]) => {
    const el = document.getElementById(`badge-${page}`);
    if (!el) return;
    if (count > 0) {
      el.textContent = count > 99 ? '99+' : count;
      el.style.display = 'flex';
    } else {
      el.style.display = 'none';
    }
  });
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(str || ''));
  return d.innerHTML;
}

// Polling de notificações a cada 30s
carregarNotificacoes();
setInterval(carregarNotificacoes, 30000);

// ---- Character counter ----
document.querySelectorAll('[data-maxlength]').forEach(el => {
  const max     = parseInt(el.dataset.maxlength);
  const targetId = el.dataset.counter;
  const counter  = targetId ? document.getElementById(targetId) : null;
  if (!counter) return;
  const update = () => {
    const len = el.value.length;
    counter.textContent = `${len}/${max}`;
    counter.className = 'char-counter' +
      (len >= max ? ' at-limit' : len >= max * 0.85 ? ' near-limit' : '');
  };
  el.addEventListener('input', update);
  update();
});
</script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>

<script>
// Token CSRF global — incluido automaticamente em todo fetch POST
const CSRF_TOKEN = '<?php echo $_SESSION["csrf_token"] ?? ""; ?>';

const _fetchOriginal = window.fetch;
window.fetch = function(url, options = {}) {
  if (options.method && options.method.toUpperCase() === 'POST') {
    // FormData nao permite setar Content-Type manualmente
    // mas podemos sempre adicionar o header CSRF
    if (options.body instanceof FormData) {
      options.headers = options.headers || {};
      options.headers['X-CSRF-Token'] = CSRF_TOKEN;
    } else {
      options.headers = options.headers || {};
      if (options.headers instanceof Headers) {
        options.headers.set('X-CSRF-Token', CSRF_TOKEN);
      } else {
        options.headers['X-CSRF-Token'] = CSRF_TOKEN;
      }
    }
  }
  return _fetchOriginal(url, options);
};
</script>

<!-- Modal de expiracao de sessao -->
<div id="modalSessao" style="display:none;position:fixed;inset:0;z-index:9999;
     background:rgba(9,9,11,0.6);backdrop-filter:blur(4px);
     align-items:center;justify-content:center;padding:var(--space-4);">
  <div style="background:var(--white);border-radius:var(--radius-xl);
              box-shadow:var(--shadow-xl);padding:var(--space-8);
              max-width:420px;width:100%;text-align:center;animation:slideUp 0.3s ease;">
    <div style="width:56px;height:56px;border-radius:50%;background:#fff3cd;
                display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-4);">
      <i data-lucide="clock" width="28" height="28" style="color:#f59e0b;"></i>
    </div>
    <h3 style="font-family:var(--font-primary);font-size:20px;font-weight:var(--fw-bold);
               margin-bottom:var(--space-2);">Sessao prestes a expirar</h3>
    <p style="color:var(--secondary);font-size:14px;margin-bottom:var(--space-2);">
      Sua sessao expira em
    </p>
    <div id="contadorSessao" style="font-size:36px;font-weight:var(--fw-bold);
         color:var(--brand);font-family:var(--font-primary);margin-bottom:var(--space-6);">
      5:00
    </div>
    <p style="color:var(--secondary);font-size:13px;margin-bottom:var(--space-6);">
      Clique em "Continuar" para permanecer conectado, ou aguarde para ser redirecionado ao login.
    </p>
    <div style="display:flex;gap:var(--space-3);">
      <button onclick="encerrarSessao()" class="btn btn-outline w-100"
              style="border-color:var(--secondary);color:var(--secondary);">
        Sair agora
      </button>
      <button onclick="renovarSessao()" class="btn btn-primary w-100" id="btnRenovar">
        Continuar conectado
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  const DURACAO   = 14400;
  const AVISO     = 300;
  const INICIO    = <?php echo \App\Helpers\Session::get('session_inicio', time()); ?>;
  let modalAberto = false;
  let intervalo   = null;

  function verificarSessao() {
    const agora    = Math.floor(Date.now() / 1000);
    const restante = DURACAO - (agora - INICIO);
    if (restante <= 0) { window.location.href = '/index.php?sessao=expirada'; return; }
    if (restante <= AVISO && !modalAberto) abrirModal(restante);
  }

  function abrirModal(segundos) {
    modalAberto = true;
    const modal = document.getElementById('modalSessao');
    modal.style.display = 'flex';
    if (window.lucide) lucide.createIcons();
    let r = segundos;
    atualizar(r);
    intervalo = setInterval(() => {
      r--;
      atualizar(r);
      if (r <= 0) { clearInterval(intervalo); window.location.href = '/index.php?sessao=expirada'; }
    }, 1000);
  }

  function atualizar(s) {
    const el = document.getElementById('contadorSessao');
    if (el) el.textContent = Math.floor(s/60) + ':' + String(s%60).padStart(2,'0');
  }

  window.renovarSessao = async function() {
    const btn = document.getElementById('btnRenovar');
    btn.disabled = true;
    btn.textContent = 'Renovando...';
    try {
      const res  = await fetch('/api/auth/renovar-sessao.php', { method: 'POST' });
      const data = await res.json();
      if (data.success) {
        clearInterval(intervalo);
        modalAberto = false;
        document.getElementById('modalSessao').style.display = 'none';
      }
    } catch(e) { window.location.href = '/index.php?sessao=expirada'; }
    finally { btn.disabled = false; btn.textContent = 'Continuar conectado'; }
  };

  window.encerrarSessao = function() { window.location.href = '/api/auth/logout.php'; };

  setInterval(verificarSessao, 30000);
  verificarSessao();
})();
</script>

</body>
</html>
