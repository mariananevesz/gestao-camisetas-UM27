<?php

class Controlador
{
    private $bancoDeDados;

    public function __construct(array $configBanco)
    {
        $this->bancoDeDados = new BancoDeDados($configBanco);
    }

    public function listarCongregacoes()
    {
        return $this->bancoDeDados->listarCongregacoes();
    }

    public function listarCamisetas()
    {
        return $this->bancoDeDados->listarCamisetas();
    }

    public function cadastrarPedido(array $dados)
    {
        $pedido = $this->montarPedido($dados);
        return $this->bancoDeDados->inserirPedido($pedido);
    }

    public function atualizarPedido($pedidoId, array $dados)
    {
        if ((int) $pedidoId <= 0) {
            throw new DomainException('Pedido inválido.');
        }

        $pedido = $this->montarPedido($dados);
        $this->bancoDeDados->atualizarPedido((int) $pedidoId, $pedido);
    }

    private function montarPedido(array $dados)
    {
        $nome = preg_replace('/\s+/u', ' ', trim($dados['nome_comprador'] ?? ''));
        $congregacaoId = (int) ($dados['congregacao_id'] ?? 0);
        $telefoneDigitado = trim($dados['telefone'] ?? '');
        $telefoneNumeros = preg_replace('/\D+/', '', $telefoneDigitado);
        $quantidades = $dados['quantidades'] ?? [];

        if ($nome === '' || $this->comprimento($nome) < 3) {
            throw new DomainException('Informe o nome completo do comprador.');
        }

        if ($this->comprimento($nome) > 150) {
            throw new DomainException('O nome do comprador deve ter no máximo 150 caracteres.');
        }

        if (!$this->bancoDeDados->buscarCongregacaoPorId($congregacaoId)) {
            throw new DomainException('Selecione uma congregação válida ou a opção Balcão.');
        }

        if (strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 11) {
            throw new DomainException('Informe um telefone válido com DDD.');
        }

        if (!is_array($quantidades)) {
            throw new DomainException('As quantidades informadas são inválidas.');
        }

        $itens = [];
        foreach ($this->bancoDeDados->listarCamisetas() as $camiseta) {
            $quantidade = (int) ($quantidades[$camiseta['id']] ?? 0);
            if ($quantidade < 0 || $quantidade > 999) {
                throw new DomainException('A quantidade de cada tamanho deve estar entre 0 e 999.');
            }

            if ($quantidade > 0) {
                $itens[] = new ItemPedido(
                    (int) $camiseta['id'],
                    $camiseta['modelo'],
                    $camiseta['tamanho'],
                    (float) $camiseta['valor'],
                    $quantidade
                );
            }
        }

        if (empty($itens)) {
            throw new DomainException('Adicione pelo menos uma camiseta ao pedido.');
        }

        return new Pedido($nome, $congregacaoId, $telefoneDigitado, $itens);
    }

    private function comprimento($texto)
    {
        return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
    }

    public function buscarPedidoPorId($id)
    {
        if ((int) $id <= 0) {
            return null;
        }
        return $this->bancoDeDados->buscarPedidoPorId((int) $id);
    }

    public function listarPedidosPaginados($pagina, $porPagina)
    {
        $total = $this->bancoDeDados->contarPedidos();
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = max(1, min((int) $pagina, $totalPaginas));
        $offset = ($pagina - 1) * $porPagina;

        return [
            'pedidos' => $this->bancoDeDados->listarPedidos($porPagina, $offset),
            'pagina_atual' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_pedidos' => $total,
        ];
    }

    public function listarTodosPedidos()
    {
        return $this->bancoDeDados->listarTodosPedidos();
    }

    public function atualizarStatus($pedidoId, $statusEntrega, $statusPagamento)
    {
        $entregasValidas = ['aguardando', 'entregue'];
        $pagamentosValidos = ['pendente', 'pago'];

        if (!in_array($statusEntrega, $entregasValidas, true)) {
            throw new DomainException('Situação de entrega inválida.');
        }

        if (!in_array($statusPagamento, $pagamentosValidos, true)) {
            throw new DomainException('Situação de pagamento inválida.');
        }

        $this->bancoDeDados->atualizarStatus((int) $pedidoId, $statusEntrega, $statusPagamento);
    }

    public function excluirPedido($pedidoId)
    {
        if ((int) $pedidoId <= 0) {
            throw new DomainException('Pedido inválido.');
        }
        $this->bancoDeDados->excluirPedido((int) $pedidoId);
    }

    public function obterDashboard()
    {
        return [
            'resumo' => $this->bancoDeDados->obterResumoDashboard(),
            'ranking' => $this->bancoDeDados->listarVendasPorCongregacao(true),
        ];
    }
}
