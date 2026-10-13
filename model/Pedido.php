<?php

class Pedido
{
    private $nomeComprador;
    private $congregacaoId;
    private $telefone;
    private $itens;

    public function __construct($nomeComprador, $congregacaoId, $telefone, array $itens)
    {
        $this->nomeComprador = $nomeComprador;
        $this->congregacaoId = $congregacaoId;
        $this->telefone = $telefone;
        $this->itens = $itens;
    }

    public function getNomeComprador() { return $this->nomeComprador; }
    public function getCongregacaoId() { return $this->congregacaoId; }
    public function getTelefone() { return $this->telefone; }
    public function getItens() { return $this->itens; }

    public function getQuantidadeTotal()
    {
        return array_reduce($this->itens, function ($total, $item) {
            return $total + $item->getQuantidade();
        }, 0);
    }

    public function getValorTotal()
    {
        return array_reduce($this->itens, function ($total, $item) {
            return $total + $item->getSubtotal();
        }, 0.0);
    }
}
