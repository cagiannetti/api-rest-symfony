<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response; //usamos librería http fountadion para manejar peticiones http
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;


use App\Entity\User; //cargamos las entidades
use App\Entity\video;
use App\Services\JwtAuth;




class UserController extends AbstractController
{

    private function resjson($data){
        /*este método sirve para devolver json
        a Victor no le andaba el método que trae, por eso lo creó
        a mí sí me andaba el return $this->json($user);
        */

        // Serializar datos utilizando el servicio serializer de symfony
        $json = $this->get('serializer')->serialize($data, 'json');

        // Response con http fountadion, creamos el objeto de respuesta
        $response = new Response();

        // Asignar contenido a la respuesta
        $response->setContent($json);

        // Indicar formatos de respuesta
        $response->headers->set('Content-Type', 'application/json');

        //Devolver respuesta
        return $response;

    }

    public function index()
    {

        //utilizamos el repositorio para interactuar con la BD para tener acceso a una serie de métodos
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $video_repo = $this->getDoctrine()->getRepository(Video::class);

        //Listar todos los usuarios con sus videos
        $users = $user_repo->findAll();
        $user = $user_repo->find(1);
        $videos = $video_repo->findAll();

        $data = [
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ];

        /*
        foreach($users as $user){
            echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";

            foreach($user->getVideos() as $video){
                echo "<p>{$video->getTitle()} - {$video->getUser()->getEmail()}</p>";
            }
        }
        */

        //return $this->json($videos);
        return $this->resjson($videos);
    }

    public function create(Request $request){

        // Recoger los datos por post
        $json = $request->get('json', null);

        // Decodificar el json
        $params = json_decode($json);


        // Hacer una respuesta por defecto, array
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'El usuario no se ha creado'
        ];

        // Comprobar y validar datos
        if($json != null){
            $name = (!empty($params->name)) ? $params->name : null;
            $surname = (!empty($params->surname)) ? $params->surname : null;
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if(!empty($email) && count($validate_email) == 0 && !empty($password) && !empty($name) && !empty($surname)){
                
                // Si la validación es correcta, crear el objeto del usuario
                $user = new User();
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setRole('ROLE_USER');
                $user->setCreatedAt(new \Datetime('now'));

                // cifrar la contraseña
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                // Comprobar si el usuario existe (evitar duplicados) utilizando entity manager
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();

                $user_repo = $doctrine->getRepository(User::class);
                $isset_user = $user_repo->findBy(array(
                    'email' => $email,
                ));

                // Si no existe guardarlo en la BD
                if(count($isset_user) == 0){
                    //Guardar el usuario
                    $em->persist($user); //pone los datos en cola para luego hacer las consultas a la bd
                    $em->flush(); //concreta todas las consultas en cola, en este caso guarda los datos en la bd definitivamente

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Usuario creado con EXITO',
                        'user' => $user
                    ];
                }else{
                    $data = [
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'El usuario ya existe'
                    ];
                }
            }
        }

        // Devolver la respuesta en json
        //return new JsonResponse($data);
        return $this->resjson($data);
    }

    public function login(Request $request, JwtAuth $jwt_auth){
        // Recibir los datos por post
        $json = $request->get('json', null);
        $params = json_decode($json);

        // Array de datos por defeto para devolver
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'El usuario no se ha podido identificar'
        ];

        // Comprobar y validar datos
        if($json != null){

            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
            $gettoken = (!empty($params->gettoken)) ? $params->gettoken : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email,[
                new Email()
            ]);

            if(!empty($email) && !empty($password) && count($validate_email) == 0){
                 // cifrar la contraseña
                $pwd = hash('sha256', $password);

                // Si todo es válido, llamaremos a un servicio para identificar a un usuario (jwt, devolverá token ó un objeto)
                if($gettoken){
                    $signup = $jwt_auth->signup($email, $pwd, $gettoken);
                }else{
                    $signup = $jwt_auth->signup($email, $pwd);
                }
                // Devolvemos respuesta de éxito
                return new JsonResponse($signup);
            }
        }
        // Devolvemos respuesta trucha
        return $this->resjson($data);
    }

    public function edit(Request $request, JwtAuth $jwt_auth){

        // Recoger la cabecera de autenticación
        $token = $request->headers->get('Authorization');

        // Crear método para comprobar si el token es correcto
        $authCheck = $jwt_auth->checkToken($token);

        //Array de respuesta por defecto
            $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Usuario no actualizado'
        ];

        // Si es correcto hacer la actualización del usuario
        if($authCheck){
            // Conseguir el entity manager
            $em = $this->getDoctrine()->getManager();

            // Conseguir datos del usuario identificado
            $identity= $jwt_auth->checkToken($token, true);

            // Conseguir el usuario a actualzar
            $user_repo = $this->getDoctrine()->getRepository(User::class);
            $user = $user_repo->findOneBy([
                'id' => $identity->sub
            ]);

            // Recoger los datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);

            // Comprobar y validar los datos
            if(!empty($json)){
                $name = (!empty($params->name)) ? $params->name : null;
                $surname = (!empty($params->surname)) ? $params->surname : null;
                $email = (!empty($params->email)) ? $params->email : null;

                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [
                    new Email()
                ]);

                if(!empty($email) && count($validate_email) == 0 && !empty($name) && !empty($surname)){
                    // Asignar nuevos datos al objeto de usuario
                    $user->setName($name);
                    $user->setSurname($surname);
                    $user->setEmail($email);

                    // Comprobar duplicados
                    $isset_user = $user_repo->findBy([
                        'email' => $email
                    ]);

                    if(count($isset_user) == 0 || $identity->email == $email){
                        // Guardar cambios en la bd
                        $em->persist($user);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Usuario actualizado',
                            'user' => $user
                        ];

                    }else{
                        $data = [
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'No puedes usar ese email, Usuario ya existe'
                        ];
                    }
                }
            }
        }

        // Devolvemos respuesta
        return $this->resjson($data);

    }
}
