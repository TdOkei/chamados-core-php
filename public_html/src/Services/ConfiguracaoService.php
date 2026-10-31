<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ConfiguracaoRepository;

class ConfiguracaoService
{
    public const CH_LOGO   = 'remessa_logo';
    public const CH_LINHA1 = 'remessa_linha1';
    public const CH_LINHA2 = 'remessa_linha2';

    private const LOGO_MAX_BYTES = 2 * 1024 * 1024;
    private const LOGO_MIN_PX    = 50;
    private const LOGO_MAX_PX    = 3000;

    private ConfiguracaoRepository $repo;

    public function __construct()
    {
        $this->repo = new ConfiguracaoRepository();
    }

    private function ok(string $m, array $d = []): array
    {
        return ['success' => true, 'message' => $m, 'data' => $d];
    }

    private function fail(string $m): array
    {
        return ['success' => false, 'message' => $m, 'data' => []];
    }

    private function dirLogo(): string
    {
        return rtrim($_ENV['UPLOADS_PATH'] ?? '/var/www/uploads', '/') . '/servicos';
    }

    public function obterRemessa(): array
    {
        $c = $this->repo->todas();
        return [
            'logo'   => $c[self::CH_LOGO]   ?? null,
            'linha1' => (string) ($c[self::CH_LINHA1] ?? ''),
            'linha2' => (string) ($c[self::CH_LINHA2] ?? ''),
        ];
    }

    /** Caminho absoluto do logo atual, ou null. */
    public function caminhoLogo(): ?string
    {
        $nome = $this->repo->obter(self::CH_LOGO);
        if (!$nome || !preg_match('/^cfg_logo_remessa_[0-9]+_[a-f0-9]+\.(jpg|png)$/', $nome)) {
            return null;
        }
        $p = $this->dirLogo() . '/' . $nome;
        return is_file($p) ? $p : null;
    }

    public function salvarTextos(string $linha1, string $linha2): array
    {
        $linha1 = trim($linha1);
        $linha2 = trim($linha2);
        if (mb_strlen($linha1) > 120 || mb_strlen($linha2) > 120) {
            return $this->fail('Cada linha de texto pode ter no máximo 120 caracteres.');
        }
        $this->repo->salvar(self::CH_LINHA1, $linha1 === '' ? null : $linha1);
        $this->repo->salvar(self::CH_LINHA2, $linha2 === '' ? null : $linha2);
        AuditoriaService::escola('editar', 0, 'Textos do cabeçalho da Remessa atualizados.');
        return $this->ok('Textos salvos.');
    }

    public function salvarLogo(array $arq): array
    {
        if (($arq['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->fail('Erro no envio do logo. Verifique o tamanho (máx. 2 MB).');
        }
        if ($arq['size'] > self::LOGO_MAX_BYTES) {
            return $this->fail('Logo muito grande. Máximo 2 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($arq['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return $this->fail('Formato inválido. Envie PNG ou JPG.');
        }

        $dim = @getimagesize($arq['tmp_name']);
        if ($dim === false) {
            return $this->fail('O arquivo não é uma imagem válida.');
        }
        [$w, $h] = $dim;
        if ($w < self::LOGO_MIN_PX || $h < self::LOGO_MIN_PX) {
            return $this->fail('Imagem muito pequena (mínimo 50 x 50 px).');
        }
        if ($w > self::LOGO_MAX_PX || $h > self::LOGO_MAX_PX) {
            return $this->fail('Imagem muito grande (máximo 3000 x 3000 px).');
        }

        $dir = $this->dirLogo();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return $this->fail('Não foi possível preparar a pasta de imagens no servidor.');
        }

        $ext  = $mime === 'image/png' ? 'png' : 'jpg';
        $nome = 'cfg_logo_remessa_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($arq['tmp_name'], $dir . '/' . $nome)) {
            return $this->fail('Erro ao salvar o logo no servidor.');
        }

        $antigo = $this->caminhoLogo();
        $this->repo->salvar(self::CH_LOGO, $nome);
        if ($antigo) {
            @unlink($antigo);
        }
        AuditoriaService::escola('editar', 0, 'Logo da Remessa atualizado.');
        return $this->ok('Logo salvo.');
    }

    public function removerLogo(): array
    {
        $antigo = $this->caminhoLogo();
        $this->repo->salvar(self::CH_LOGO, null);
        if ($antigo) {
            @unlink($antigo);
        }
        AuditoriaService::escola('editar', 0, 'Logo da Remessa removido.');
        return $this->ok('Logo removido.');
    }
}
