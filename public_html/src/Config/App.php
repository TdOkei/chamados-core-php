<?php

declare(strict_types=1);

namespace App\Config;

class App
{
    // Sessão
    public const SESSION_TIMEOUT      = 18000; // 5 horas em segundos
    public const MAX_LOGIN_ATTEMPTS   = 6;

    // Limites de texto
    public const MAX_DESCRICAO_SERVICO   = 300;
    public const MAX_OBS_ITEM_FALTA      = 400;
    public const MAX_DESCRICAO_SUGESTAO  = 400;
    public const MAX_DESCRICAO_OPORT     = 400;

    // Status de solicitações
    public const STATUS_PENDENTE          = 'pendente';
    public const STATUS_EM_ANALISE        = 'em_analise';
    public const STATUS_EM_PROCESSAMENTO  = 'em_processamento';
    public const STATUS_DESPACHADO        = 'despachado';
    public const STATUS_RETORNADO         = 'retornado';
    public const STATUS_ITEM_EM_FALTA     = 'item_em_falta';
    public const STATUS_CONCLUIDO         = 'concluido';
    public const STATUS_CANCELADO         = 'cancelado';
    public const STATUS_REJEITADO         = 'rejeitado';

    // Labels de status para exibição
    public const STATUS_LABELS = [
        'pendente'          => 'Pendente',
        'em_analise'        => 'Em Análise',
        'em_processamento'  => 'Em Processamento',
        'despachado'        => 'Despachado',
        'retornado'         => 'Retornado',
        'item_em_falta'     => 'Item em Falta',
        'concluido'         => 'Concluído',
        'cancelado'         => 'Cancelado',
        'rejeitado'         => 'Rejeitado',
    ];

    // Cores de badge por status
    public const STATUS_COLORS = [
        'pendente'          => 'badge-pending',
        'em_analise'        => 'badge-analysis',
        'em_processamento'  => 'badge-processing',
        'despachado'        => 'badge-dispatched',
        'retornado'         => 'badge-returned',
        'item_em_falta'     => 'badge-missing',
        'concluido'         => 'badge-done',
        'cancelado'         => 'badge-cancelled',
        'rejeitado'         => 'badge-rejected',
    ];

    // Transições válidas por perfil
    // perfil => [status_atual => [status_permitidos]]
    public const TRANSICOES_VALIDAS = [
        'administrador' => [
            'pendente'         => ['em_analise', 'rejeitado'],
            'em_analise'       => ['em_processamento', 'rejeitado'],
            'em_processamento' => ['despachado'],
            'retornado'        => ['pendente'],
            'item_em_falta'    => ['pendente'],
        ],
        'diretor' => [
            'pendente' => ['cancelado'],
        ],
	'escriturario' => [
            'pendente' => ['cancelado'],
        ],
        'estagiario' => [
            'pendente' => ['cancelado'],
        ],
        // QR Code transitions (internal, via QrCodeService)
        'qrcode' => [
            'despachado' => ['concluido', 'retornado', 'item_em_falta'],
        ],
    ];

    // Perfis
    public const PERFIL_SUPERADMIN    = 'superadmin';
    public const PERFIL_ADMINISTRADOR = 'administrador';
    public const PERFIL_DIRETOR       = 'diretor';
    public const PERFIL_SECRETARIO    = 'secretario';
    public const PERFIL_ESCRITURARIO  = 'escriturario';
    public const PERFIL_ESTAGIARIO    = 'estagiario';

    // Perfis com as mesmas permissões operacionais do Diretor na escola
    public const PERFIS_OPERACIONAIS_ESCOLA = [self::PERFIL_DIRETOR, self::PERFIL_ESCRITURARIO, self::PERFIL_ESTAGIARIO];

    // Perfis de escola que acessam Oportunidades (estagiario NAO acessa)
    public const PERFIS_OPORTUNIDADES_ESCOLA = [self::PERFIL_DIRETOR, self::PERFIL_ESCRITURARIO];

    // Categorias
    public const CATEGORIA_PRODUTO  = 'produto';
    public const CATEGORIA_SERVICO  = 'servico';

    // Tipos de notificação
    public const NOTIF_NOVA_SOLICITACAO    = 'nova_solicitacao';
    public const NOTIF_STATUS_ATUALIZADO   = 'status_atualizado';
    public const NOTIF_NOVA_OPORTUNIDADE   = 'nova_oportunidade';
    public const NOTIF_QR_RESULTADO        = 'qr_resultado';
    public const NOTIF_NOVA_SUGESTAO       = 'nova_sugestao';
    public const NOTIF_SOLICITACAO_CONCLUIDA = 'solicitacao_concluida';
}
