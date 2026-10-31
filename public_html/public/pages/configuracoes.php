<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
use App\Middleware\AuthMiddleware;
use App\Services\ConfiguracaoService;
AuthMiddleware::requireSuperadmin();
$cfg        = (new ConfiguracaoService())->obterRemessa();
$pageTitle  = 'Configurações da Remessa';
$activePage = 'configuracoes';
require_once __DIR__ . '/../../templates/layout-header.php';
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<div class="animate-fade-in">
  <div class="mb-6">
    <h2 class="text-h2">Configurações da Remessa</h2>
    <p class="text-body" style="color:var(--secondary);margin-top:4px;">
      Cabeçalho do documento impresso que acompanha cada entrega (papel A4).
    </p>
  </div>

  <form id="formCfg" class="card" style="padding:var(--space-6);max-width:640px;" onsubmit="salvar(event)">
    <div style="margin-bottom:var(--space-5);">
      <label class="form-label">Logo / brasão (PNG ou JPG, até 2 MB)</label>
      <div style="display:flex;align-items:center;gap:var(--space-4);flex-wrap:wrap;">
        <div style="width:120px;height:120px;border:2px dashed var(--neutral-200);border-radius:var(--radius-lg);
                    display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden;">
          <img id="prevLogo" alt="Logo"
               src="<?= $cfg['logo'] ? '/api/upload/logo.php?v=' . $h($cfg['logo']) : '' ?>"
               style="max-width:100%;max-height:100%;<?= $cfg['logo'] ? '' : 'display:none;' ?>">
          <span id="semLogo" style="font-size:12px;color:var(--secondary);<?= $cfg['logo'] ? 'display:none;' : '' ?>">Sem logo</span>
        </div>
        <div>
          <input type="file" id="logo" accept="image/png,image/jpeg" class="form-control" onchange="previa(this)">
          <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:13px;">
            <input type="checkbox" id="removerLogo"> Remover logo atual
          </label>
        </div>
      </div>
    </div>

    <div style="margin-bottom:var(--space-4);">
      <label class="form-label">Linha 1 (ex.: Prefeitura Municipal de ...)</label>
      <input type="text" id="linha1" class="form-control" maxlength="120" value="<?= $h($cfg['linha1']) ?>">
    </div>
    <div style="margin-bottom:var(--space-5);">
      <label class="form-label">Linha 2 (ex.: Secretaria Municipal de Educação)</label>
      <input type="text" id="linha2" class="form-control" maxlength="120" value="<?= $h($cfg['linha2']) ?>">
    </div>

    <button type="submit" id="btnSalvar" class="btn btn-primary">Salvar</button>
  </form>
</div>

<script>
function previa(input) {
  const f = input.files && input.files[0];
  if (!f) return;
  const img = document.getElementById('prevLogo');
  img.src = URL.createObjectURL(f);
  img.style.display = '';
  document.getElementById('semLogo').style.display = 'none';
  document.getElementById('removerLogo').checked = false;
}

async function salvar(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  const fd = new FormData();
  fd.append('linha1', document.getElementById('linha1').value);
  fd.append('linha2', document.getElementById('linha2').value);
  if (document.getElementById('removerLogo').checked) fd.append('remover_logo', '1');
  const f = document.getElementById('logo').files[0];
  if (f) fd.append('logo', f);
  try {
    const res  = await fetch('/api/configuracoes/index.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      toast('Configurações salvas.', 'success');
      const img = document.getElementById('prevLogo');
      if (json.data && json.data.logo) {
        img.src = '/api/upload/logo.php?v=' + encodeURIComponent(json.data.logo);
        img.style.display = '';
        document.getElementById('semLogo').style.display = 'none';
      } else {
        img.removeAttribute('src');
        img.style.display = 'none';
        document.getElementById('semLogo').style.display = '';
      }
      document.getElementById('logo').value = '';
      document.getElementById('removerLogo').checked = false;
    } else {
      toast(json.message || 'Erro ao salvar.', 'danger');
    }
  } catch (err) {
    toast('Erro de conexão.', 'danger');
  }
  btn.disabled = false;
}
</script>

<?php require_once __DIR__ . '/../../templates/layout-footer.php'; ?>
