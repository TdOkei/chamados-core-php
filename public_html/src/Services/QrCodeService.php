<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SolicitacaoRepository;
use App\Repositories\UsuarioRepository;
use App\Config\App;
use App\Config\Database;
use App\Services\CacheService;

class QrCodeService
{
    private SolicitacaoRepository $repo;
    private NotificacaoService    $notif;

    public function __construct()
    {
        $this->repo  = new SolicitacaoRepository();
        $this->notif = new NotificacaoService();
    }

    /**
     * Valida token — retorna cenário sem alterar status.
     */
    public function validarToken(string $token, int $idDiretor, int $idEscola): array
    {
        $token = trim($token);
        if (empty($token)) return $this->fail('Token inválido.');

        $solicitacao = $this->repo->buscarPorToken($token);

        if (!$solicitacao) {
            $this->logErro($token, $idDiretor, 'erro_token', 'Token não encontrado ou invalidado.');
            return $this->fail('QR Code inválido ou já utilizado.');
        }

        if (!in_array($solicitacao['status'], [App::STATUS_DESPACHADO, App::STATUS_ITEM_EM_FALTA], true)) {
            return $this->fail('Esta solicitação não está aguardando confirmação de entrega.');
        }

        // Escola inativa (de quem le ou dona do pedido): solicitacao congelada
        $repoEscola = new \App\Repositories\EscolaRepository();
        if (!$repoEscola->estaAtiva($idEscola)) {
            return $this->fail('Sua escola está temporariamente indisponível no sistema. Entre em contato com o suporte da Secretaria de Ensino.');
        }
        if (!$repoEscola->estaAtiva((int) $solicitacao['escola_id'])) {
            return $this->fail('Esta solicitação pertence a uma escola temporariamente indisponível e está congelada.');
        }

        // Escola errada
        if ((int) $solicitacao['escola_id'] !== $idEscola) {
            $this->repo->registrarLogQr(
                (int) $solicitacao['id'], $idDiretor, $token, 'erro_destino',
                $this->ip(), $this->ua(),
                "Escola esperada ID {$solicitacao['escola_id']}, lido por escola ID {$idEscola}"
            );
            return [
                'success' => true,
                'message' => 'QR Code lido.',
                'data'    => [
                    'cenario'       => 'destino_errado',
                    'id_solicitacao'=> (int) $solicitacao['id'],
                    'escola_destino'=> $solicitacao['escola_nome'],
                    'token'         => $token,
                ],
            ];
        }

        $itens           = [];
        $itensJaEntregues = [];
        if ($solicitacao['categoria'] === 'produto') {
            $itens            = $this->repo->buscarItens((int) $solicitacao['id']);
            $itensJaEntregues = $this->repo->buscarItensEntregues((int) $solicitacao['id']);
        }

        return [
            'success' => true,
            'message' => 'QR Code válido.',
            'data'    => [
                'cenario'            => 'valido',
                'id_solicitacao'     => (int) $solicitacao['id'],
                'escola_nome'        => $solicitacao['escola_nome'],
                'categoria'          => $solicitacao['categoria'],
                'token'              => $token,
                'itens'              => $itens,
                'itens_ja_entregues' => $itensJaEntregues,
                'exige_foto_conclusao' => $this->exigeFotoConclusao($solicitacao),
            ],
        ];
    }

    /**
     * Processa resultado definitivo: retornado | item_em_falta | concluido.
     */
    public function processarResultado(string $token, int $idDiretor, int $idEscola, string $cenario, string $observacao, array $body = []): array
    {
        $solicitacao = $this->repo->buscarPorToken($token);
        if (!$solicitacao) return $this->fail('Token inválido ou já utilizado.');

        if (!in_array($solicitacao['status'], [App::STATUS_DESPACHADO, App::STATUS_ITEM_EM_FALTA], true)) {
            return $this->fail('Solicitação não está aguardando confirmação.');
        }

        // Escola inativa (de quem le ou dona do pedido): solicitacao congelada
        $repoEscola = new \App\Repositories\EscolaRepository();
        if (!$repoEscola->estaAtiva($idEscola)) {
            return $this->fail('Sua escola está temporariamente indisponível no sistema. Entre em contato com o suporte da Secretaria de Ensino.');
        }
        if (!$repoEscola->estaAtiva((int) $solicitacao['escola_id'])) {
            return $this->fail('Esta solicitação pertence a uma escola temporariamente indisponível e está congelada.');
        }

        $obs    = trim($observacao);
        $idSol  = (int) $solicitacao['id'];
        $stAnt  = $solicitacao['status'];

        // RETORNADO
        if ($cenario === 'retornado') {
            $this->repo->atualizarStatus($idSol, App::STATUS_RETORNADO);
            $this->repo->registrarHistorico($idSol, $idDiretor, $stAnt, App::STATUS_RETORNADO, 'QR lido com destino incorreto. Retornado.');
            $this->repo->registrarLogQr($idSol, $idDiretor, $token, 'retornado', $this->ip(), $this->ua(), 'Destino incorreto confirmado.');
            $this->notifAdmin($solicitacao, 'retornado');
            CacheService::invalidarSolicitacoes();
            return $this->ok('Pedido marcado como Retornado e será recolocado na fila.', ['status_novo' => App::STATUS_RETORNADO]);
        }

        // ITEM EM FALTA
        if ($cenario === 'item_em_falta') {
            $itensFaltando  = $body['itens_faltando']  ?? [];
            $itensEntregues = $body['itens_entregues'] ?? [];

            if (empty($itensFaltando)) return $this->fail('Selecione ao menos um item faltante.');

            // Busca nomes dos itens faltando para compor observacao
            $todosItens = $this->repo->buscarItens($idSol);
            $nomesItens = [];
            foreach ($todosItens as $item) {
                if (in_array((int)$item['id'], array_map('intval', $itensFaltando))) {
                    $nomesItens[] = $item['produto_nome'] . ' (' . number_format((float)$item['quantidade'], 2, ',', '.') . ' ' . $item['unidade_sigla'] . ')';
                }
            }
            $nomeRecebedor = trim($observacao);
            if (empty($nomeRecebedor)) return $this->fail('Informe o nome de quem recebeu os itens.');
            if (mb_strlen($nomeRecebedor) > 150) return $this->fail('Nome muito longo (max. 150 caracteres).');

            $obs = 'Recebido por: ' . $nomeRecebedor . '. Itens faltantes: ' . implode(', ', $nomesItens);

            // Registra log do QR e captura o id inserido
            $this->repo->registrarLogQr($idSol, $idDiretor, $token, 'item_em_falta', $this->ip(), $this->ua(), $obs);
            $idLog = $this->repo->ultimoLogQrId();

            // Grava itens ja entregues nesta remessa
            foreach ($itensEntregues as $idItem) {
                $this->repo->registrarItemEntregue($idSol, (int)$idItem, $idLog);
            }

            $this->repo->atualizarStatus($idSol, App::STATUS_ITEM_EM_FALTA, ['obs_item_falta' => $obs]);
            $this->repo->registrarHistorico($idSol, $idDiretor, $stAnt, App::STATUS_ITEM_EM_FALTA, $obs);
            $this->notifAdmin($solicitacao, 'item_em_falta');
            CacheService::invalidarSolicitacoes();
            return $this->ok('Registrado. O pedido voltará para a fila para correção.', ['status_novo' => App::STATUS_ITEM_EM_FALTA]);
        }

        // CONCLUÍDO
        if ($cenario === 'concluido') {
            if (empty($obs)) return $this->fail('Informe o nome de quem recebeu o pedido.');
            if (mb_strlen($obs) > 150) return $this->fail('Nome muito longo (máx. 150 caracteres).');
            $nomeFoto = null;
            if ($this->exigeFotoConclusao($solicitacao)) {
                $rf = $this->salvarFotoConclusao($idSol, $body['_foto'] ?? null);
                if (!$rf['success']) return $rf;
                $nomeFoto = $rf['data']['arquivo'];
            }
            $this->repo->atualizarStatus($idSol, App::STATUS_CONCLUIDO, ['nome_recebedor' => $obs, 'qr_invalidado' => true, 'foto_conclusao' => $nomeFoto]);
            $this->repo->registrarHistorico($idSol, $idDiretor, $stAnt, App::STATUS_CONCLUIDO, "Recebido por: {$obs}");
            $this->repo->registrarLogQr($idSol, $idDiretor, $token, 'sucesso', $this->ip(), $this->ua(), "Recebido por: {$obs}");
            $this->notifAdmin($solicitacao, 'concluido');
            $this->notifSuperadmins($idSol, $solicitacao['escola_nome']);
            CacheService::invalidarSolicitacoes();
            return $this->ok('Entrega confirmada! Solicitação concluída com sucesso.', [
                'status_novo'    => App::STATUS_CONCLUIDO,
                'nome_recebedor' => $obs,
            ]);
        }

        return $this->fail('Cenário inválido.');
    }

    // ----------------------------------------------------------------
    private function exigeFotoConclusao(array $sol): bool
    {
        return ($sol['categoria'] ?? '') === 'servico' && !empty($sol['foto_servico']);
    }

    private function salvarFotoConclusao(int $idSol, $arq): array
    {
        if (!is_array($arq) || ($arq['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->fail('Envie a foto do serviço concluído.');
        }
        if ($arq['size'] > 5 * 1024 * 1024) {
            return $this->fail('Foto muito grande. Máximo 5 MB.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($arq['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true) || @getimagesize($arq['tmp_name']) === false) {
            return $this->fail('Foto inválida. Envie uma imagem JPG ou PNG.');
        }
        $dir = rtrim($_ENV['UPLOADS_PATH'] ?? '/var/www/uploads', '/') . '/servicos';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return $this->fail('Não foi possível preparar a pasta de imagens no servidor.');
        }
        $ext  = $mime === 'image/png' ? 'png' : 'jpg';
        $nome = 'con_' . $idSol . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($arq['tmp_name'], $dir . '/' . $nome)) {
            return $this->fail('Erro ao salvar a foto no servidor.');
        }
        return $this->ok('Foto salva.', ['arquivo' => $nome]);
    }

    private function notifAdmin(array $sol, string $resultado): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE perfil='administrador' AND categoria=:cat AND ativo=1 LIMIT 1");
        $stmt->execute([':cat' => $sol['categoria']]);
        $adminId = $stmt->fetchColumn();
        if ($adminId) $this->notif->qrResultado((int)$adminId, (int)$sol['id'], $resultado);
    }

    private function notifSuperadmins(int $idSol, string $escolaNome): void
    {
        $uRepo = new UsuarioRepository();
        foreach ($uRepo->listarTodosSuperadmins() as $adminId) {
            $this->notif->solicitacaoConcluida((int)$adminId, $idSol, $escolaNome);
        }
    }

    private function logErro(string $token, int $idDiretor, string $resultado, string $obs): void
    {
        try {
            $db = Database::getInstance();
            $db->prepare('INSERT INTO log_qrcode (id_solicitacao,id_diretor,token_usado,resultado,ip,user_agent,observacao) VALUES (0,:dir,:tok,:res,:ip,:ua,:obs)')
               ->execute([':dir'=>$idDiretor,':tok'=>$token,':res'=>$resultado,':ip'=>$this->ip(),':ua'=>$this->ua(),':obs'=>$obs]);
        } catch (\Throwable $e) {}
    }

    private function ip(): string  { return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''; }
    private function ua(): string  { return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255); }
    private function ok(string $m, array $d = []): array   { return ['success'=>true, 'message'=>$m, 'data'=>$d]; }
    private function fail(string $m): array                 { return ['success'=>false,'message'=>$m,'data'=>[]]; }
}
