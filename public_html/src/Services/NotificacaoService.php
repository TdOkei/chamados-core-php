<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificacaoRepository;
use App\Config\App;

class NotificacaoService
{
    private NotificacaoRepository $repo;

    public function __construct()
    {
        $this->repo = new NotificacaoRepository();
    }

    // ----------------------------------------------------------------
    // Disparadores semânticos (usados por outros Services)
    // ----------------------------------------------------------------

    /**
     * Notifica administrador da categoria sobre nova solicitação.
     */
    public function novaSolicitacao(int $idAdmin, int $idSolicitacao, string $escola, string $categoria): void
    {
        $cat = $categoria === 'produto' ? 'Produto' : 'Serviço';
        $this->repo->criar(
            idUsuario: $idAdmin,
            tipo:      App::NOTIF_NOVA_SOLICITACAO,
            titulo:    "Nova solicitação de {$cat}",
            mensagem:  "A escola {$escola} criou uma nova solicitação de {$cat}.",
            idRef:     $idSolicitacao,
            refTipo:   'solicitacao'
        );
    }

    /**
     * Notifica diretor sobre mudança de status em sua solicitação.
     */
    public function statusAtualizado(int $idDiretor, int $idSolicitacao, string $statusNovo): void
    {
        $label = App::STATUS_LABELS[$statusNovo] ?? $statusNovo;

        if ($statusNovo === App::STATUS_DESPACHADO) {
            $titulo   = "Pedido a caminho!";
            $mensagem = "Seu pedido #{$idSolicitacao} foi despachado e esta a caminho da sua escola. Fique atento ao recebimento e use o QR Code para confirmar a entrega.";
        } elseif ($statusNovo === App::STATUS_REJEITADO) {
            $titulo   = "Solicitacao rejeitada";
            $mensagem = "Sua solicitacao #{$idSolicitacao} foi rejeitada. Acesse o sistema para mais detalhes.";
        } elseif ($statusNovo === App::STATUS_ITEM_EM_FALTA) {
            $titulo   = "Item em falta registrado";
            $mensagem = "O recebimento do pedido #{$idSolicitacao} foi registrado com itens faltantes. O pedido voltara para a fila para nova remessa.";
        } else {
            $titulo   = "Solicitacao atualizada";
            $mensagem = "Sua solicitacao #{$idSolicitacao} foi atualizada para: {$label}.";
        }

        $this->repo->criar(
            idUsuario: $idDiretor,
            tipo:      App::NOTIF_STATUS_ATUALIZADO,
            titulo:    $titulo,
            mensagem:  $mensagem,
            idRef:     $idSolicitacao,
            refTipo:   'solicitacao'
        );
    }

    /**
     * Notifica diretores sobre nova oportunidade.
     */
    public function novaOportunidade(array $idsDiretores, int $idOportunidade, string $titulo): void
    {
        foreach ($idsDiretores as $idDiretor) {
            $this->repo->criar(
                idUsuario: (int) $idDiretor,
                tipo:      App::NOTIF_NOVA_OPORTUNIDADE,
                titulo:    "Nova oportunidade disponível",
                mensagem:  "Há uma nova oportunidade: {$titulo}. Acesse para aceitar ou rejeitar.",
                idRef:     $idOportunidade,
                refTipo:   'oportunidade'
            );
        }
    }

    /**
     * Notifica administrador sobre resultado de leitura de QR Code.
     */
    public function qrResultado(int $idAdmin, int $idSolicitacao, string $resultado): void
    {
        $msgs = [
            'concluido'     => 'QR Code validado com sucesso. Solicitação concluída.',
            'retornado'     => 'QR Code lido com erro de destino. Solicitação retornada.',
            'item_em_falta' => 'QR Code lido. Diretor reportou item faltando no pedido.',
        ];
        $msg = $msgs[$resultado] ?? "QR Code lido. Status: {$resultado}.";

        $this->repo->criar(
            idUsuario: $idAdmin,
            tipo:      App::NOTIF_QR_RESULTADO,
            titulo:    "QR Code lido",
            mensagem:  $msg,
            idRef:     $idSolicitacao,
            refTipo:   'solicitacao'
        );
    }

    /**
     * Notifica superadmin sobre nova sugestão de produto.
     */
    public function novaSugestao(int $idSuperadmin, int $idSugestao, string $escola): void
    {
        $this->repo->criar(
            idUsuario: $idSuperadmin,
            tipo:      App::NOTIF_NOVA_SUGESTAO,
            titulo:    "Nova sugestão de produto",
            mensagem:  "A escola {$escola} sugeriu um novo produto. Verifique a fila de sugestões.",
            idRef:     $idSugestao,
            refTipo:   'sugestao'
        );
    }

    /**
     * Notifica superadmin sobre solicitação concluída.
     */
    public function novoComentario(int $idDestinatario, int $idSolicitacao, string $nomeAutor): void
    {
        $this->repo->criar(
            idUsuario: $idDestinatario,
            tipo:      App::NOTIF_STATUS_ATUALIZADO,
            titulo:    "Novo comentario na solicitacao",
            mensagem:  "{$nomeAutor} comentou na solicitacao #{$idSolicitacao}.",
            idRef:     $idSolicitacao,
            refTipo:   'solicitacao'
        );
    }

    public function solicitacaoConcluida(int $idSuperadmin, int $idSolicitacao, string $escola): void
    {
        $this->repo->criar(
            idUsuario: $idSuperadmin,
            tipo:      App::NOTIF_SOLICITACAO_CONCLUIDA,
            titulo:    "Solicitação concluída",
            mensagem:  "A escola {$escola} confirmou o recebimento de uma solicitação.",
            idRef:     $idSolicitacao,
            refTipo:   'solicitacao'
        );
    }

    // ----------------------------------------------------------------
    // API: listar / marcar lidas
    // ----------------------------------------------------------------

    public function listar(int $idUsuario): array
    {
        $notifs   = $this->repo->listar($idUsuario);
        $naoLidas = $this->repo->contarNaoLidas($idUsuario);
        $perfil   = $_SESSION['usuario_perfil'] ?? '';
        $badges   = $this->repo->contarBadges($idUsuario, $perfil);

        // Formata tempo relativo
        $agora = time();
        foreach ($notifs as &$n) {
            $criado      = strtotime($n['created_at']);
            $diff        = $agora - $criado;
            $n['tempo_relativo'] = $this->tempoRelativo($diff);
            $n['lida']   = (bool) $n['lida'];
        }

        return [
            'notificacoes' => $notifs,
            'nao_lidas'    => $naoLidas,
            'badges'       => $badges,
        ];
    }

    public function marcarLida(int $id, int $idUsuario): void
    {
        $this->repo->marcarLida($id, $idUsuario);
    }

    public function marcarTodasLidas(int $idUsuario): void
    {
        $this->repo->marcarTodasLidas($idUsuario);
    }

    // ----------------------------------------------------------------
    // Privado
    // ----------------------------------------------------------------

    private function tempoRelativo(int $segundos): string
    {
        return match (true) {
            $segundos < 60     => 'Agora mesmo',
            $segundos < 3600   => (int)($segundos / 60) . ' min atrás',
            $segundos < 86400  => (int)($segundos / 3600) . ' h atrás',
            $segundos < 604800 => (int)($segundos / 86400) . ' dias atrás',
            default            => date('d/m/Y', time() - $segundos),
        };
    }
}
