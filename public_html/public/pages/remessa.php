<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Config\App;
use App\Helpers\Session;
use App\Middleware\AuthMiddleware;
use App\Services\RemessaService;

AuthMiddleware::requirePerfil([App::PERFIL_ADMINISTRADOR, App::PERFIL_SUPERADMIN]);

$h  = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$dt = fn($v) => $v ? date('d/m/Y H:i', strtotime((string) $v)) : '';

$id  = (int) ($_GET['id'] ?? 0);
$res = $id
    ? (new RemessaService())->preparar($id, (string) Session::getPerfil(), Session::getCategoria())
    : ['success' => false, 'message' => 'Solicitação inválida.', 'data' => []];

if (!$res['success']) {
    http_response_code(403);
    ?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Remessa</title>
    <link rel="stylesheet" href="/assets/css/design-system.css"></head>
    <body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;">
      <div style="text-align:center;max-width:420px;padding:24px;">
        <h2>Remessa indisponível</h2>
        <p style="color:#666;"><?= $h($res['message']) ?></p>
        <button onclick="window.close()" style="padding:8px 16px;">Fechar</button>
      </div></body></html><?php
    exit;
}

$d   = $res['data'];
$sol = $d['sol'];
$num = str_pad((string) $sol['id'], 4, '0', STR_PAD_LEFT);
$rotulos = [
    'pendente' => 'Solicitado', 'em_analise' => 'Em análise', 'em_processamento' => 'Em processamento',
    'despachado' => 'Despachado', 'retornado' => 'Retornado', 'item_em_falta' => 'Item em falta',
    'concluido' => 'Concluído', 'cancelado' => 'Cancelado', 'rejeitado' => 'Rejeitado',
];
$ehServico = $sol['categoria'] === 'servico';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Remessa #<?= $h($num) ?></title>
<style>
  @page { size: A4; margin: 12mm; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e5e7eb; font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; }
  .barra { position: sticky; top: 0; background: #111827; color: #fff; padding: 10px 16px; display: flex; gap: 10px; align-items: center; justify-content: center; }
  .barra button { padding: 8px 16px; border: 0; border-radius: 6px; cursor: pointer; font-size: 14px; }
  .barra .prim { background: #ef4444; color: #fff; }
  .folha { width: 210mm; min-height: 297mm; margin: 16px auto; background: #fff; padding: 12mm; box-shadow: 0 2px 12px rgba(0,0,0,.2); position: relative; }
  .cab { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
  .cab img { max-height: 90px; max-width: 220px; display: block; margin: 0 auto 8px; }
  .cab .l1 { font-size: 15px; font-weight: bold; }
  .cab .l2 { font-size: 13px; }
  .titulo { display: flex; justify-content: space-between; align-items: baseline; margin: 8px 0 14px; }
  .titulo h1 { font-size: 24px; margin: 0; letter-spacing: 1px; }
  .titulo .via { font-size: 12px; font-weight: bold; border: 1px solid #111; padding: 3px 8px; }
  .destino { border: 2px solid #111; padding: 10px 14px; margin-bottom: 14px; }
  .destino .rot { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #555; }
  .destino .nome { font-size: 22px; font-weight: bold; margin: 2px 0 4px; }
  h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin: 16px 0 6px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; vertical-align: top; }
  th { background: #f3f4f6; font-size: 11px; }
  td.n, th.n { text-align: right; white-space: nowrap; }
  .duas { display: flex; gap: 16px; align-items: flex-start; }
  .duas .col-tab { flex: 1; }
  .qrbox { width: 52mm; text-align: center; border: 2px solid #111; padding: 8px; }
  .qrbox img { width: 100%; display: block; }
  .qrbox p { margin: 6px 0 0; font-size: 10px; }
  .assin { margin-top: 26px; display: flex; gap: 16px; }
  .assin div { flex: 1; border-top: 1px solid #111; padding-top: 4px; font-size: 11px; }
  .carimbo { border: 4px double #166534; color: #166534; padding: 14px; text-align: center; margin: 14px 0; }
  .carimbo b { font-size: 22px; display: block; letter-spacing: 1px; }
  .foto-conc { margin-top: 14px; break-inside: avoid; page-break-inside: avoid; }
  .foto-conc img { display: block; max-width: 100%; max-height: 80mm; margin: 0 auto; border: 1px solid #999; object-fit: contain; }
  .foto-conc .leg { font-size: 10px; color: #555; text-align: center; margin-top: 4px; }
  .rodape { position: absolute; left: 12mm; right: 12mm; bottom: 8mm; font-size: 10px; color: #555; display: flex; justify-content: space-between; }
  @media print {
    body { background: #fff; }
    .barra { display: none; }
    .folha { margin: 0; box-shadow: none; width: auto; min-height: 0; padding: 0; }
    .rodape { position: static; margin-top: 18px; }
  }
</style>
</head>
<body>
<div class="barra">
  <button class="prim" onclick="window.print()">Imprimir / Salvar PDF</button>
  <button onclick="window.close()">Fechar</button>
</div>

<div class="folha">
  <div class="cab">
    <?php if (!empty($d['cfg']['logo'])): ?>
      <img src="/api/upload/logo.php?v=<?= $h($d['cfg']['logo']) ?>" alt="Logo">
    <?php endif; ?>
    <?php if ($d['cfg']['linha1'] !== ''): ?><div class="l1"><?= $h($d['cfg']['linha1']) ?></div><?php endif; ?>
    <?php if ($d['cfg']['linha2'] !== ''): ?><div class="l2"><?= $h($d['cfg']['linha2']) ?></div><?php endif; ?>
  </div>

  <div class="titulo">
    <h1>REMESSA Nº <?= $h($num) ?></h1>
    <?php if ($d['via'] > 1 && !$d['concluida']): ?>
      <span class="via"><?= $d['via'] ?>ª VIA</span>
    <?php endif; ?>
  </div>

  <div class="destino">
    <div class="rot">Destino</div>
    <div class="nome"><?= $h($d['escola']['nome']) ?></div>
    <?php if (!empty($d['escola']['endereco'])): ?><div><?= $h($d['escola']['endereco']) ?></div><?php endif; ?>
    <?php if (!empty($d['escola']['telefone'])): ?><div>Tel.: <?= $h($d['escola']['telefone']) ?></div><?php endif; ?>
    <div style="margin-top:4px;">Solicitante: <?= $h($sol['diretor_nome']) ?> &nbsp;|&nbsp; Tipo: <?= $ehServico ? 'Serviço' : 'Produto' ?></div>
  </div>

  <?php if ($d['concluida']): ?>
    <div class="carimbo">
      <b>ENTREGA CONCLUÍDA</b>
      Recebido por <?= $h($sol['nome_recebedor']) ?><?= $d['conclusao_em'] ? ' em ' . $h($dt($d['conclusao_em'])) : '' ?><br>
      Este documento é apenas uma cópia para arquivo. O QR Code não é mais válido.
    </div>
  <?php endif; ?>

  <div class="duas">
    <div class="col-tab">
      <h3><?= $ehServico ? 'Serviço' : 'Itens da remessa' ?></h3>
      <?php if ($ehServico): ?>
        <table><tr><td><?= $h($sol['descricao_servico']) ?></td></tr></table>
        <?php if ($d['itens']): ?>
          <table style="margin-top:6px;">
            <tr><th>Item</th><th>Unid.</th><th class="n">Qtd.</th></tr>
            <?php foreach ($d['itens'] as $i): ?>
              <tr><td><?= $h($i['produto_nome']) ?></td><td><?= $h($i['unidade_sigla']) ?></td><td class="n"><?= $h(number_format((float) $i['quantidade'], 2, ',', '.')) ?></td></tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      <?php else: ?>
        <table>
          <tr><th style="width:30px;">Nº</th><th>Produto</th><th>Unid.</th><th class="n">Quantidade</th></tr>
          <?php foreach ($d['itens'] as $k => $i): ?>
            <tr>
              <td><?= $k + 1 ?></td>
              <td><?= $h($i['produto_nome']) ?></td>
              <td><?= $h($i['unidade_sigla']) ?></td>
              <td class="n"><?= $h(number_format((float) $i['quantidade'], 2, ',', '.')) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>

      <h3>Movimentação</h3>
      <table>
        <tr><th style="width:120px;">Data / hora</th><th>Situação</th></tr>
        <?php foreach ($d['historico'] as $hst): ?>
          <tr><td><?= $h($dt($hst['created_at'])) ?></td><td><?= $h($rotulos[$hst['status_novo']] ?? $hst['status_novo']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>

    <?php if ($d['qr_url']): ?>
    <div class="qrbox">
      <img id="qrImg" alt="QR Code">
      <p><b>Confirme o recebimento</b><br>Aponte a câmera do celular para este QR Code ao receber a remessa.</p>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($d['concluida'] && !empty($sol['foto_conclusao'])): ?>
  <div class="foto-conc">
    <h3>Foto do serviço concluído</h3>
    <img src="/api/upload/imagem.php?arquivo=<?= urlencode($sol['foto_conclusao']) ?>" alt="Foto do serviço concluído">
    <div class="leg">Registrada pela escola na confirmação da entrega<?= $d['conclusao_em'] ? ' em ' . $h($dt($d['conclusao_em'])) : '' ?>.</div>
  </div>
  <?php endif; ?>

  <?php if (!$d['concluida']): ?>
  <div class="assin">
    <div>Recebido por (nome legível)</div>
    <div style="max-width:40mm;">Data</div>
    <div>Assinatura</div>
  </div>
  <?php endif; ?>

  <div class="rodape">
    <span>Emitida em <?= date('d/m/Y H:i') ?></span>
    <span>Remessa Nº <?= $h($num) ?><?= $d['via'] > 1 && !$d['concluida'] ? ' — ' . $d['via'] . 'ª via' : '' ?></span>
  </div>
</div>

<?php if ($d['qr_url']): ?>
<script src="/assets/js/qrcode.js"></script>
<script src="/assets/js/qr-local.js"></script>
<script>
  document.getElementById('qrImg').src = tdQrDataUrl(<?= json_encode($d['qr_url'], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>, 600);
</script>
<?php endif; ?>
<script>
  window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });
</script>
</body>
</html>
