<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response; //usamos librería http fountadion para manejar peticiones http
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;

use Knp\Component\Pager\PaginatorInterface;

use App\Entity\User; //cargamos las entidades
use App\Entity\video;
use App\Services\JwtAuth;

class VideoController extends AbstractController
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
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }

    public function create(Request $request, JwtAuth $jwt_auth){

        // Respuesta Array por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'El video no se ha podido crear'
        ];

        // Recoger el token
        $token = $request->headers->get('Authorization', null);

        // Comprobar token
        $authCheck = $jwt_auth->checkToken($token);
        
        
        if($authCheck){
            
            // Recoger datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);

            // Recoger objeto de usuario identificado
            $identity = $jwt_auth->checkToken($token, true);

            // Comprobar y validar datos
            if(!empty($json)){

                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $title = (!empty($params->title)) ? $params->title : null;
                $description = (!empty($params->description)) ? $params->description : null;
                $url =(!empty($params->url)) ? $params->url : null;

                if(!empty($user_id) && !empty($title)){
                    // Guardar el nuevo video favorito en la bd
                    
                    $em = $this->getDoctrine()->getManager();
                    
                    //busco el usuario en la bd
                    $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                        'id' => $user_id
                    ]);

                    //Crear y guardar el objeto
                    $video = new Video();
                    $video->setUser($user);
                    $video->setTitle($title);
                    $video->setDescription($description);
                    $video->setUrl($url);
                    $video->setStatus('normal');
                    
                    $createdAt = new \Datetime('now');
                    $updatedAt = new \Datetime('now');

                    $video->setCreatedAt($createdAt);
                    $video->setUpdatedAt($updatedAt);

                    //Guardar en bd - persistir
                    $em->persist($video);
                    $em->flush();

                    // Respuesta de éxito
                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El video se ha podido guardado',
                        'video' => $video
                    ];
                }
            }
        }
        // Devolver respuesta
        return $this->resjson($data);
    }

    public function videos(Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator){

        // Recoger la cabecera de autenticación
        $token = $request->headers->get('Authorization', null);

        // Comprobar el token
        $authCheck = $jwt_auth->checkToken($token);

        // Si es válido...
        if($authCheck){
            
            // Conseguir la identidad del usuario
            $identity = $jwt_auth->checkToken($token, true);

            /*  Configurar el bundle de paginación: 
                hecho en los archivos services.yaml y bundles.php*/
            
            $em = $this->getDoctrine()->getManager();
            
            // Hacer consulta para paginar, utiliza lenguaje dql, es como sql pero utlizando objetos de doctrine
            $dql = "SELECT v FROM App\Entity\Video v WHERE v.user = {$identity->sub} ORDER BY v.id DESC";
            $query = $em->createQuery($dql);

            // Recoger el parámetro page de la url que viene por get, usa getInt porque lo que viene esun número
            $page = $request->query->getInt('page', 1);
            $items_per_page = 5;

            // Invocar método de paginación
            $pagination = $paginator->paginate($query, $page, $items_per_page);
            $total = $pagination->getTotalItemCount();
            
            // Datos de respuesta de éxito
            
            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'videos' => $pagination,
                'user' => $identity,
                'user_id' => $identity->sub

            ];

        }else{

            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'No se pueden listar los videos en este momento'
            ];

        }




        // Devolver respuesta
        return $this->resjson($data);
    }
}
