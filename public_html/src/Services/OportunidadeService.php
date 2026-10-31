<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\{OportunidadeRepository, SolicitacaoRepository};
use App\Config\App;

class OportunidadeService
{
    private OportunidadeRepository $repo;
    private SolicitacaoRepository  $solRepo;
    private NotificacaoService     $notif;

    public function __construct()
    {
        $this->repo    = new OportunidadeRepository();
        $this->solRepo = new SolicitacaoRepository();
        $this->notif   = new NotificacaoService();
    }

    // ================================================================
    // ADMINISTRADOR: criar / editar / desabilitar
    // ================================================================

    public function criar(array $dados, int $idAdmin): array
    {
        $erro = $this->validar($dados);
        if ($erro) return $this->fail($erro);

        $idEscolas = array_filter(array_map('intval', $dados['escolas'] ?? []));
        if (empty($idEscolas)) return $this->fail('Selecione pelo menos uma escola destinatária.');

        // Escolas inativas nao recebem oportunidades
        $totalSelecionadas = count($idEscolas);
        $idEscolas = array_values(array_filter($idEscolas, fn($e) => $this->escolaAtiva((int) $e)));
        if (empty($idEscolas)) return $this->fail('Nenhuma das escolas selecionadas está ativa.');

        $pedirQtd  = !empty($dados['pedir_quantidade']) ? 1 : 0;
        $idUnidade = ($pedirQtd && !empty($dados['id_unidade_quantidade'])) ? (int)$dados['id_unidade_quantidade'] : null;

        $id = $this->repo->criar([
            'id_admin'              => $idAdmin,
            'titulo'                => trim($dados['titulo']),
            'descricao'             => trim($dados['descricao']),
            'pedir_quantidade'      => $pedirQtd,
            'id_unidade_quantidade' => $idUnidade,
            'categoria'             => $dados['categoria'],
        ]);

        // Insere itens (se produto)
        if ($dados['categoria'] === 'produto') {
            foreach ($dados['itens'] ?? [] as $item) {
                $this->repo->inserirItem($id, (int) $item['id_produto'], (int) $item['id_unidade'], (float) $item['quantidade']);
            }
        }

        // Vincula escolas
        $this->repo->vincularEscolas($id, $idEscolas);

        // Notifica diretores
        $diretores = $this->repo->buscarDiretoresDasEscolas($id);
        $this->notif->novaOportunidade($diretores, $id, trim($dados['titulo']));

        $ignoradas = $totalSelecionadas - count($idEscolas);
        $msgOk = 'Oportunidade criada com sucesso.'
            . ($ignoradas > 0 ? " {$ignoradas} escola(s) inativa(s) não receberam." : '');
        return $this->ok($msgOk, ['id' => $id]);
    }

    public function atualizar(int $id, array $dados, int $idAdmin): array
    {
        $oportunidade = $this->repo->buscarPorId($id);
        if (!$oportunidade) return $this->fail('Oportunidade não encontrada.');
        if ((int) $oportunidade['id_admin'] !== $idAdmin) return $this->fail('Sem permissão para editar esta oportunidade.');
        if (!$oportunidade['editavel']) return $this->fail('Esta oportunidade não pode mais ser editada pois alguma escola já tomou uma decisão.');

        $erro = $this->validar($dados);
        if ($erro) return $this->fail($erro);

        $pedirQtd  = !empty($dados['pedir_quantidade']) ? 1 : 0;
        $idUnidade = ($pedirQtd && !empty($dados['id_unidade_quantidade'])) ? (int)$dados['id_unidade_quantidade'] : null;

        $this->repo->atualizar($id, [
            'titulo'                => trim($dados['titulo']),
            'descricao'             => trim($dados['descricao']),
            'pedir_quantidade'      => $pedirQtd,
            'id_unidade_quantidade' => $idUnidade,
            'categoria' => $dados['categoria'],
        ]);

        // Atualiza itens
        if ($dados['categoria'] === 'produto') {
            $this->repo->removerItens($id);
            foreach ($dados['itens'] ?? [] as $item) {
                $this->repo->inserirItem($id, (int) $item['id_produto'], (int) $item['id_unidade'], (float) $item['quantidade']);
            }
        }

        return $this->ok('Oportunidade atualizada com sucesso.');
    }

    public function desabilitar(int $id, int $idAdmin): array
    {
        $oportunidade = $this->repo->buscarPorId($id);
        if (!$oportunidade) return $this->fail('Oportunidade não encontrada.');
        if ((int) $oportunidade['id_admin'] !== $idAdmin) return $this->fail('Sem permissão.');
        if ($oportunidade['status'] === 'desabilitada') return $this->fail('Oportunidade já está desabilitada.');

        $this->repo->alterarStatus($id, 'desabilitada');
        return $this->ok('Oportunidade desabilitada. Quem já tomou decisão mantém seu histórico.');
    }

    public function cancelar(int $id, int $idAdmin): array
    {
        $oportunidade = $this->repo->buscarPorId($id);
        if (!$oportunidade) return $this->fail('Oportunidade não encontrada.');
        if ((int) $oportunidade['id_admin'] !== $idAdmin) return $this->fail('Sem permissão.');

        $this->repo->alterarStatus($id, 'cancelada');
        return $this->ok('Oportunidade cancelada.');
    }

    // ================================================================
    // DIRETOR: aceitar / rejeitar
    // ================================================================

    public function decidir(int $idOportunidade, string $decisao, int $idDiretor, int $idEscola, string $escolaNome, ?float $quantidade = null): array
    {
        if (!in_array($decisao, ['aceita', 'rejeitada'], true)) {
            return $this->fail('Decisão inválida.');
        }

        // Escola inativa: oportunidades ficam congeladas
        if (!$this->escolaAtiva($idEscola)) {
            return $this->fail('Sua escola está temporariamente indisponível no sistema. Entre em contato com o suporte da Secretaria de Ensino.');
        }

        $oportunidade = $this->repo->buscarPorId($idOportunidade);
        if (!$oportunidade) return $this->fail('Oportunidade não encontrada.');
        if ($oportunidade['status'] !== 'ativa') return $this->fail('Esta oportunidade não está mais disponível.');

        // Verifica se escola foi convidada e ainda não decidiu
        $decisaoAtual = $this->repo->buscarDecisaoEscola($idOportunidade, $idEscola);
        if (!$decisaoAtual) return $this->fail('Sua escola não foi convidada para esta oportunidade.');
        if ($decisaoAtual['decisao'] !== 'pendente') return $this->fail('Você já tomou uma decisão sobre esta oportunidade.');

        $idSolicitacao = null;

        if ($decisao === 'aceita') {
            // Valida quantidade se oportunidade pede
            if ($oportunidade['pedir_quantidade'] && (empty($quantidade) || $quantidade <= 0)) {
                return $this->fail('Informe a quantidade desejada.');
            }
            $dadosAceite = ['quantidade' => $quantidade];
            $idSolicitacao = $this->criarSolicitacaoDaOportunidade($oportunidade, $idDiretor, $idEscola, $escolaNome, $dadosAceite);
        }

        // Registra decisão
        $this->repo->registrarDecisao($idOportunidade, $idEscola, $decisao, $idSolicitacao);

        // Marca não editável na primeira decisão de qualquer escola
        if ($oportunidade['editavel'] && !$this->repo->temAlgumaDecisao($idOportunidade)) {
            // (já tem a decisão acima, então marca agora)
        }
        $this->repo->marcarNaoEditavel($idOportunidade);

        $msg = $decisao === 'aceita'
            ? 'Oportunidade aceita! Uma solicitação foi criada automaticamente.'
            : 'Oportunidade rejeitada.';

        return $this->ok($msg, ['id_solicitacao' => $idSolicitacao]);
    }

    // ================================================================
    // Leitura
    // ================================================================

    public function listar(?int $idAdmin = null): array
    {
        return $this->repo->listar($idAdmin);
    }

    public function listarParaEscola(int $idEscola): array
    {
        return $this->repo->listarParaEscola($idEscola);
    }

    public function buscarDetalhes(int $id): ?array
    {
        $op = $this->repo->buscarPorId($id);
        if (!$op) return null;
        $op['itens']  = $this->repo->buscarItens($id);
        $op['escolas']= $this->repo->buscarEscolasVinculadas($id);
        return $op;
    }

    public function buscarHistoricoEscola(int $idEscola): array
    {
        return $this->repo->buscarHistoricoEscola($idEscola);
    }

    // ================================================================
    // Privados
    // ================================================================

    private function criarSolicitacaoDaOportunidade(array $oportunidade, int $idDiretor, int $idEscola, string $escolaNome, array $dadosAceite = []): int
    {
        $dadosSol = [
            'id_escola'         => $idEscola,
            'id_diretor'        => $idDiretor,
            'categoria'         => $oportunidade['categoria'],
            'descricao_servico' => $oportunidade['descricao'],
        ];

        $idSol = $this->solRepo->criar($dadosSol);

        // Se produto, copia itens da oportunidade
        if ($oportunidade['categoria'] === 'produto') {
            $itens = $this->repo->buscarItens((int) $oportunidade['id']);
            if ($oportunidade['pedir_quantidade'] && !empty($dadosAceite['quantidade']) && !empty($itens)) {
                // Diretor informou a quantidade — usa o produto do primeiro item e a unidade definida pelo admin
                $idUnidade = !empty($oportunidade['id_unidade_quantidade'])
                    ? (int) $oportunidade['id_unidade_quantidade']
                    : (int) $itens[0]['id_unidade'];
                $this->solRepo->inserirItem(
                    $idSol,
                    (int) $itens[0]['id_produto'],
                    $idUnidade,
                    (float) $dadosAceite['quantidade']
                );
            } else {
                foreach ($itens as $item) {
                    $this->solRepo->inserirItem(
                        $idSol,
                        (int) $item['id_produto'],
                        (int) $item['id_unidade'],
                        (float) $item['quantidade']
                    );
                }
            }
        }

        $this->solRepo->registrarHistorico($idSol, $idDiretor, null, App::STATUS_PENDENTE, 'Solicitação gerada automaticamente por aceite de oportunidade.');

        // Notifica admin da categoria
        $db   = \App\Config\Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE perfil = 'administrador' AND categoria = :cat AND ativo = 1 LIMIT 1");
        $stmt->execute([':cat' => $oportunidade['categoria']]);
        $adminId = $stmt->fetchColumn();
        if ($adminId) {
            $notif = new NotificacaoService();
            $notif->novaSolicitacao((int) $adminId, $idSol, $escolaNome, $oportunidade['categoria']);
        }

        return $idSol;
    }

    private function validar(array $dados): ?string
    {
        $titulo    = trim($dados['titulo']    ?? '');
        $descricao = trim($dados['descricao'] ?? '');
        $categoria = $dados['categoria'] ?? '';

        if (empty($titulo)) return 'O título é obrigatório.';
        if (strlen($titulo) > 150) return 'Título muito longo (máx. 150 caracteres).';
        if (empty($descricao)) return 'A descrição é obrigatória.';
        if (strlen($descricao) > 400) return 'Descrição muito longa (máx. 400 caracteres).';
        if (!in_array($categoria, ['produto', 'servico'], true)) return 'Categoria inválida.';

        if ($categoria === 'produto') {
            $itens = $dados['itens'] ?? [];
            if (empty($itens)) return 'Adicione pelo menos um item à oportunidade de produto.';
            foreach ($itens as $i => $item) {
                $n = $i + 1;
                if (empty($item['id_produto'])) return "Item {$n}: selecione o produto.";
                if (empty($item['id_unidade'])) return "Item {$n}: selecione a unidade.";
                if (empty($item['quantidade'])  || (float)$item['quantidade'] <= 0) return "Item {$n}: quantidade inválida.";
            }
        }

        return null;
    }

    /** Escola ativa? (regra de congelamento de escola inativa) */
    private function escolaAtiva(int $idEscola): bool
    {
        return (new \App\Repositories\EscolaRepository())->estaAtiva($idEscola);
    }

    private function ok(string $message, array $data = []): array
    {
        return ['success' => true, 'message' => $message, 'data' => $data];
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message, 'data' => []];
    }
}
