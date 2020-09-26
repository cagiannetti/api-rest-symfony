<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User; //cargamos las entidades
use App\Entity\video;




class UserController extends AbstractController
{

    public function index()
    {

        //utilizamos el repositorio para interactuar con la BD para tener acceso a una serie de métodos
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $video_repo = $this->getDoctrine()->getRepository(Video::class);

        //Listar todos los usuarios con sus videos
        $users = $user_repo->findAll();

        foreach($users as $user){
            echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";

            foreach($user->getVideos() as $video){
                echo "<p>{$video->getTitle()} - {$video->getUser()->getEmail()}</p>";
            }
        }

        die;


        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
}
