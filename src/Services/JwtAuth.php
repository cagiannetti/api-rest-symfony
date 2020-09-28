<?php
namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth{

    public $manager; // defino propiedad pública me da acceso al que fue cargado en services.yaml orm doctrine
    public $key;

    public function __construct($manager){
        $this->manager = $manager;
        $this->key = 'hola_que_tal_este_es_el_master_fullstack_58752384593';
    }


    public function signup($email, $password, $gettoken = null){

        // Comprobar si el usuario existe
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);

        $signup = false;

        if(is_object($user)){
            $signup = true;
        }

        //  Si existe generar el token jwt
        if($signup){

            $token = [
                'sub' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            ];

            // Comprobar el flag gettoken , condicional
            $jwt = JWT::encode($token, $this->key, 'HS256');
            if(!empty($gettoken)){
                $data = $jwt;  
            }else{
                $decoded = JWT::decode($jwt, $this->key, ['HS256']);
                $data = $decoded;
            }

        }else{

            $data = [
                'status' => 'error',
                'message' => 'login incorrecto'
            ];

        }


        // Devolver datos, la variable $data puede tener 3 estados según lo fuimos cargando durante todo el método
        return $data;
    }
}