/* Gera QR Code localmente (sem serviço externo). Requer qrcode.js (MIT, Kazuhiko Arase). */
function tdQrDataUrl(texto, px) {
  var qr = qrcode(0, 'M');
  qr.addData(texto);
  qr.make();
  var n = qr.getModuleCount(), margem = 4;
  var cel = Math.max(1, Math.floor((px || 400) / (n + 2 * margem)));
  var tam = cel * (n + 2 * margem);
  var c = document.createElement('canvas');
  c.width = tam; c.height = tam;
  var x = c.getContext('2d');
  x.fillStyle = '#ffffff'; x.fillRect(0, 0, tam, tam);
  x.fillStyle = '#000000';
  for (var r = 0; r < n; r++)
    for (var k = 0; k < n; k++)
      if (qr.isDark(r, k)) x.fillRect((k + margem) * cel, (r + margem) * cel, cel, cel);
  return c.toDataURL('image/png');
}
