<?php

class Congregacao
{
    private $id;
    private $nome;
    private $setor;
    private $tipo;
    private $participaRanking;

    public function __construct($id, $nome, $setor, $tipo, $participaRanking)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->setor = $setor;
        $this->tipo = $tipo;
        $this->participaRanking = $participaRanking;
    }

    public function getId() { return $this->id; }
    public function getNome() { return $this->nome; }
    public function getSetor() { return $this->setor; }
    public function getTipo() { return $this->tipo; }
    public function participaRanking() { return $this->participaRanking; }
}
