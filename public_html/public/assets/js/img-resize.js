/* Reduz a foto no navegador antes do envio (evita falhas por limite de tamanho).
   Devolve um Blob JPEG; se não for possível reduzir, devolve o arquivo original. */
function tdReduzirImagem(arquivo, maxLado, qualidade) {
  maxLado = maxLado || 1600;
  qualidade = qualidade || 0.85;
  return new Promise(function (resolve) {
    if (!arquivo || !/^image\/(jpeg|png)$/.test(arquivo.type)) { resolve(arquivo); return; }
    var url = URL.createObjectURL(arquivo);
    var img = new Image();
    img.onload = function () {
      URL.revokeObjectURL(url);
      var w = img.naturalWidth, h = img.naturalHeight;
      var esc = Math.min(1, maxLado / Math.max(w, h));
      var c = document.createElement('canvas');
      c.width = Math.max(1, Math.round(w * esc));
      c.height = Math.max(1, Math.round(h * esc));
      var ctx = c.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, c.width, c.height);
      ctx.drawImage(img, 0, 0, c.width, c.height);
      c.toBlob(function (b) {
        resolve(b && b.size < arquivo.size ? b : arquivo);
      }, 'image/jpeg', qualidade);
    };
    img.onerror = function () { URL.revokeObjectURL(url); resolve(arquivo); };
    img.src = url;
  });
}
