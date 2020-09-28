<?php
namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth{

    public $manager; // defino propiedad pública me da acceso al que fue cargado en services.yaml orm doctrine

    public function __construct($manager){
        $this->manager = $manager;
    }


    public function signup(){
        return "hola mundo desde el servicio jwt";
    }
}