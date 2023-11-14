<?php

class Jugador{
    
    private $nombre;
    private $mano = array();

    public function __construct($nombre) {
        $this->nombre = $nombre;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getMano() {
        return $this->mano;
    }

    public function setMano($cartas) {
        $this->mano = $cartas;
    }
    
}


?>