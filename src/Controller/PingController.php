<?php

namespace App\Controller;

use App\Entity\Ping;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PingController extends AbstractController
{
    /**
     * @OA\Response (
     *     response="200",
     *     description="Ping saved in database",
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
     *     example="2022-11-16 09:54:22 ",
     *     @OA\Schema(type="string")
     *
     * )* @OA\Parameter(
     *     name="trail",
     *     in="query",
     *     description="Trail id",
     *     example="25",
     * @OA\Schema(type="string")
     * )
     *
     * @OA\Tag(name="Ping")
     * @Route("/ping", name="Ping",methods={"POST"})
     */
    public function ping(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ping = new Ping();

        $ping->setIsLogged($request->query->get('isLogged'));
        $ping->setIsLocated($request->query->get('isLocated'));
        $ping->setIsOnline($request->query->get('isOnline'));
        $ping->setIsCloseToTrail($request->query->get('isCloseToTrail'));
        $ping->setTrail($request->query->get('trail'));
        $ping->setDate($request->query->get('date'));

        $entityManager->persist($ping);
        $entityManager->flush();

        $response = new JsonResponse($error ?? $ping, 200,);

        return $response;
    }
}
