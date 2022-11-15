<?php

namespace App\Controller;

use App\Entity\Ping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PingController extends AbstractController
{
    /**
     * @OA\Response (
     *     response="200",
     *     description="xxxxxxxxxxxxxxxxxxx",
     *     @OA\JsonContent(
     *         @OA\Schema(type="string", example="thisisatokenlol")
     *     )
     * )
     * @OA\Parameter(
     *     name="isLogged",
     *     in="query",
     *     description="Is the user logged in ?",
     *     example="true",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="isLocated",
     *     in="query",
     *     description="Is the user located ?",
     *     example="false",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="isCloseToTrail",
     *     in="query",
     *     description="Is the user close to a registered trail ?",
     *     example="false",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="isOnline",
     *     in="query",
     *     description="Is the user online ?",
     *     example="true",
     *     @OA\Schema(type="boolean")
     * )
     * @OA\Parameter(
     *     name="date",
     *     in="query",
     *     description="Date & Time of the ping",
     *     example="2022-11-14 ",
     *     @OA\Schema(type="dateTime")
     * )
     * 
     * @OA\Tag(name="Ping")     
     * @Route("/ping", name="app_ping",methods={"POST"})
     */
    public function ping(Request $request): Response
    {
        

        // return $this->render('ping/index.html.twig', [
        //     'controller_name' => 'PingController',
        // ]);
    }
}
