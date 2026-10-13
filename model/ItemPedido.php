<?php

class ItemPedido
{
    private $camisetaId;
    private $modelo;
    private $tamanho;
    private $valorUnitario;
    private $quantidade;

    public function __construct($camisetaId, $modelo, $tamanho, $valorUnitario, $quantidade)
    {
        $this->camisetaId = $camisetaId;
        $this->modelo = $modelo;
        $this->tamanho = $tamanho;
        $this->valorUnitario = $valorUnitario;
        $this->quantidade = $quantidade;
    }

    public function getCamisetaId() { return $this->camisetaId; }
    public function getModelo() { return $this->modelo; }
    public function getTamanho() { return $this->tamanho; }
    public function getValorUnitario() { return $this->valorUnitario; }
    public function getQuantidade() { return $this->quantidade; }
    public function getSubtotal() { return $this->valorUnitario * $this->quantidade; }
}
