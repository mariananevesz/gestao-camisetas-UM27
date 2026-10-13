<?php

class BancoDeDados
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function conectarBD()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conexao = new mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['password'],
                $this->config['name'],
                $this->config['port']
            );
            $conexao->set_charset('utf8mb4');
            return $conexao;
        } catch (mysqli_sql_exception $erro) {
            throw new RuntimeException(
                'Não foi possível conectar ao banco de dados. Confira o arquivo config/config.php e importe database/umadepresp.sql.'
            );
        }
    }

    public function listarCongregacoes()
    {
        $conexao = $this->conectarBD();
        $resultado = $conexao->query(
            'SELECT id, nome, setor, tipo, participa_ranking, ordem_exibicao
             FROM congregacoes
             ORDER BY ordem_exibicao ASC, id ASC'
        );
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    public function buscarCongregacaoPorId($id)
    {
        $conexao = $this->conectarBD();
        $comando = $conexao->prepare(
            'SELECT id, nome, setor, tipo, participa_ranking, ordem_exibicao
             FROM congregacoes WHERE id = ? LIMIT 1'
        );
        $comando->bind_param('i', $id);
        $comando->execute();
        return $comando->get_result()->fetch_assoc();
    }

    public function listarCamisetas()
    {
        $conexao = $this->conectarBD();
        $resultado = $conexao->query(
            'SELECT id, modelo, tamanho, valor, ordem_exibicao
             FROM camisetas
             WHERE ativo = 1
             ORDER BY ordem_exibicao ASC, id ASC'
        );
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    public function inserirPedido(Pedido $pedido)
    {
        $conexao = $this->conectarBD();
        $conexao->begin_transaction();

        try {
            $nome = $pedido->getNomeComprador();
            $congregacaoId = $pedido->getCongregacaoId();
            $telefone = $pedido->getTelefone();
            $valorTotal = $pedido->getValorTotal();

            $comando = $conexao->prepare(
                "INSERT INTO pedidos
                    (numero_pedido, nome_comprador, congregacao_id, telefone, valor_total, status_entrega, status_pagamento)
                 VALUES (NULL, ?, ?, ?, ?, 'aguardando', 'pendente')"
            );
            $comando->bind_param('sisd', $nome, $congregacaoId, $telefone, $valorTotal);
            $comando->execute();

            $pedidoId = (int) $conexao->insert_id;
            $numeroPedido = 'Pedido ' . str_pad((string) $pedidoId, 7, '0', STR_PAD_LEFT);

            $atualizarNumero = $conexao->prepare('UPDATE pedidos SET numero_pedido = ? WHERE id = ?');
            $atualizarNumero->bind_param('si', $numeroPedido, $pedidoId);
            $atualizarNumero->execute();

            $this->inserirItens($conexao, $pedidoId, $pedido->getItens());
            $conexao->commit();

            return ['id' => $pedidoId, 'numero_pedido' => $numeroPedido];
        } catch (Throwable $erro) {
            $conexao->rollback();
            throw $erro;
        }
    }

    public function atualizarPedido($pedidoId, Pedido $pedido)
    {
        $conexao = $this->conectarBD();
        $conexao->begin_transaction();

        try {
            $nome = $pedido->getNomeComprador();
            $congregacaoId = $pedido->getCongregacaoId();
            $telefone = $pedido->getTelefone();
            $valorTotal = $pedido->getValorTotal();

            $comando = $conexao->prepare(
                'UPDATE pedidos
                 SET nome_comprador = ?, congregacao_id = ?, telefone = ?, valor_total = ?
                 WHERE id = ?'
            );
            $comando->bind_param('sisdi', $nome, $congregacaoId, $telefone, $valorTotal, $pedidoId);
            $comando->execute();

            if ($comando->affected_rows === 0 && !$this->pedidoExiste($conexao, $pedidoId)) {
                throw new DomainException('Pedido não encontrado.');
            }

            $excluirItens = $conexao->prepare('DELETE FROM pedido_itens WHERE pedido_id = ?');
            $excluirItens->bind_param('i', $pedidoId);
            $excluirItens->execute();

            $this->inserirItens($conexao, $pedidoId, $pedido->getItens());
            $conexao->commit();
        } catch (Throwable $erro) {
            $conexao->rollback();
            throw $erro;
        }
    }

    private function inserirItens(mysqli $conexao, $pedidoId, array $itens)
    {
        $comando = $conexao->prepare(
            'INSERT INTO pedido_itens
                (pedido_id, camiseta_id, modelo, tamanho, valor_unitario, quantidade, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($itens as $item) {
            $camisetaId = $item->getCamisetaId();
            $modelo = $item->getModelo();
            $tamanho = $item->getTamanho();
            $valorUnitario = $item->getValorUnitario();
            $quantidade = $item->getQuantidade();
            $subtotal = $item->getSubtotal();

            $comando->bind_param(
                'iissdid',
                $pedidoId,
                $camisetaId,
                $modelo,
                $tamanho,
                $valorUnitario,
                $quantidade,
                $subtotal
            );
            $comando->execute();
        }
    }

    private function pedidoExiste(mysqli $conexao, $pedidoId)
    {
        $comando = $conexao->prepare('SELECT id FROM pedidos WHERE id = ? LIMIT 1');
        $comando->bind_param('i', $pedidoId);
        $comando->execute();
        return (bool) $comando->get_result()->fetch_assoc();
    }

    public function buscarPedidoPorId($id)
    {
        $conexao = $this->conectarBD();
        $comando = $conexao->prepare(
            'SELECT p.*, c.nome AS congregacao_nome, c.tipo AS congregacao_tipo
             FROM pedidos p
             INNER JOIN congregacoes c ON c.id = p.congregacao_id
             WHERE p.id = ? LIMIT 1'
        );
        $comando->bind_param('i', $id);
        $comando->execute();
        $pedido = $comando->get_result()->fetch_assoc();

        if (!$pedido) {
            return null;
        }

        $pedido['itens'] = $this->buscarItensDoPedido($conexao, $id);
        return $pedido;
    }

    private function buscarItensDoPedido(mysqli $conexao, $pedidoId)
    {
        $comando = $conexao->prepare(
            'SELECT pi.*, c.ordem_exibicao
             FROM pedido_itens pi
             LEFT JOIN camisetas c ON c.id = pi.camiseta_id
             WHERE pi.pedido_id = ?
             ORDER BY c.ordem_exibicao ASC, pi.id ASC'
        );
        $comando->bind_param('i', $pedidoId);
        $comando->execute();
        return $comando->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function listarPedidos($limite, $offset)
    {
        $conexao = $this->conectarBD();
        $comando = $conexao->prepare(
            'SELECT p.*, c.nome AS congregacao_nome, c.tipo AS congregacao_tipo
             FROM pedidos p
             INNER JOIN congregacoes c ON c.id = p.congregacao_id
             ORDER BY p.criado_em DESC, p.id DESC
             LIMIT ? OFFSET ?'
        );
        $comando->bind_param('ii', $limite, $offset);
        $comando->execute();
        $pedidos = $comando->get_result()->fetch_all(MYSQLI_ASSOC);
        return $this->anexarItensAosPedidos($conexao, $pedidos);
    }

    public function listarTodosPedidos()
    {
        $conexao = $this->conectarBD();
        $resultado = $conexao->query(
            'SELECT p.*, c.nome AS congregacao_nome, c.tipo AS congregacao_tipo
             FROM pedidos p
             INNER JOIN congregacoes c ON c.id = p.congregacao_id
             ORDER BY p.criado_em DESC, p.id DESC'
        );
        $pedidos = $resultado->fetch_all(MYSQLI_ASSOC);
        return $this->anexarItensAosPedidos($conexao, $pedidos);
    }

    private function anexarItensAosPedidos(mysqli $conexao, array $pedidos)
    {
        if (empty($pedidos)) {
            return [];
        }

        $ids = array_map('intval', array_column($pedidos, 'id'));
        $listaIds = implode(',', $ids);
        $resultado = $conexao->query(
            "SELECT pi.*, c.ordem_exibicao
             FROM pedido_itens pi
             LEFT JOIN camisetas c ON c.id = pi.camiseta_id
             WHERE pi.pedido_id IN ($listaIds)
             ORDER BY c.ordem_exibicao ASC, pi.id ASC"
        );

        $itensPorPedido = [];
        while ($item = $resultado->fetch_assoc()) {
            $itensPorPedido[$item['pedido_id']][] = $item;
        }

        foreach ($pedidos as &$pedido) {
            $pedido['itens'] = $itensPorPedido[$pedido['id']] ?? [];
        }
        unset($pedido);

        return $pedidos;
    }

    public function contarPedidos()
    {
        $conexao = $this->conectarBD();
        $resultado = $conexao->query('SELECT COUNT(*) AS total FROM pedidos');
        return (int) $resultado->fetch_assoc()['total'];
    }

    public function atualizarStatus($pedidoId, $statusEntrega, $statusPagamento)
    {
        $conexao = $this->conectarBD();
        $comando = $conexao->prepare(
            'UPDATE pedidos SET status_entrega = ?, status_pagamento = ? WHERE id = ?'
        );
        $comando->bind_param('ssi', $statusEntrega, $statusPagamento, $pedidoId);
        $comando->execute();

        if ($comando->affected_rows === 0 && !$this->pedidoExiste($conexao, $pedidoId)) {
            throw new DomainException('Pedido não encontrado.');
        }
    }

    public function excluirPedido($pedidoId)
    {
        $conexao = $this->conectarBD();
        $comando = $conexao->prepare('DELETE FROM pedidos WHERE id = ?');
        $comando->bind_param('i', $pedidoId);
        $comando->execute();

        if ($comando->affected_rows === 0) {
            throw new DomainException('Pedido não encontrado.');
        }
    }

    public function obterResumoDashboard()
    {
        $conexao = $this->conectarBD();
        $resultado = $conexao->query(
            "SELECT
                COALESCE(SUM(valor_total), 0) AS valor_vendido,
                COALESCE(SUM(CASE WHEN status_pagamento = 'pago' THEN valor_total ELSE 0 END), 0) AS valor_recebido,
                COALESCE(SUM(CASE WHEN status_pagamento = 'pendente' THEN valor_total ELSE 0 END), 0) AS valor_pendente,
                COUNT(*) AS total_pedidos,
                COALESCE(SUM(CASE WHEN status_pagamento = 'pago' THEN 1 ELSE 0 END), 0) AS pedidos_pagos,
                COALESCE(SUM(CASE WHEN status_pagamento = 'pendente' THEN 1 ELSE 0 END), 0) AS pedidos_pendentes,
                COALESCE(SUM(CASE WHEN status_entrega = 'entregue' THEN 1 ELSE 0 END), 0) AS pedidos_entregues,
                COALESCE(SUM(CASE WHEN status_entrega = 'aguardando' THEN 1 ELSE 0 END), 0) AS pedidos_aguardando
             FROM pedidos"
        );
        $resumo = $resultado->fetch_assoc();

        $quantidade = $conexao->query(
            'SELECT COALESCE(SUM(quantidade), 0) AS total_camisetas FROM pedido_itens'
        )->fetch_assoc();
        $resumo['total_camisetas'] = (int) $quantidade['total_camisetas'];

        return $resumo;
    }

    public function listarVendasPorCongregacao($somenteRanking = false)
    {
        $conexao = $this->conectarBD();
        $condicao = $somenteRanking ? 'WHERE c.participa_ranking = 1' : '';
        $resultado = $conexao->query(
            "SELECT
                c.id,
                c.nome,
                c.tipo,
                c.ordem_exibicao,
                c.participa_ranking,
                COALESCE(SUM(pi.quantidade), 0) AS quantidade_camisetas,
                COALESCE(SUM(pi.subtotal), 0) AS valor_vendido
             FROM congregacoes c
             LEFT JOIN pedidos p ON p.congregacao_id = c.id
             LEFT JOIN pedido_itens pi ON pi.pedido_id = p.id
             $condicao
             GROUP BY c.id, c.nome, c.tipo, c.ordem_exibicao, c.participa_ranking
             ORDER BY quantidade_camisetas DESC, valor_vendido DESC, c.ordem_exibicao ASC"
        );
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    public function listarVendasPorTamanho()
    {
        $conexao = $this->conectarBD();
        $resultado = $conexao->query(
            'SELECT
                c.id,
                c.modelo,
                c.tamanho,
                c.ordem_exibicao,
                COALESCE(SUM(pi.quantidade), 0) AS quantidade_camisetas,
                COALESCE(SUM(pi.subtotal), 0) AS valor_vendido
             FROM camisetas c
             LEFT JOIN pedido_itens pi ON pi.camiseta_id = c.id
             GROUP BY c.id, c.modelo, c.tamanho, c.ordem_exibicao
             ORDER BY c.ordem_exibicao ASC'
        );
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }
}
