<?php

class Camiseta
{
    private $id;
    private $modelo;
    private $tamanho;
    private $valor;

    public function __construct($id, $modelo, $tamanho, $valor)
    {
        $this->id = $id;
        $this->modelo = $modelo;
        $this->tamanho = $tamanho;
        $this->valor = $valor;
    }

    public function getId() { return $this->id; }
    public function getModelo() { return $this->modelo; }
    public function getTamanho() { return $this->tamanho; }
    public function getValor() { return $this->valor; }
}
