<?php

namespace App\Controller;

use App\Service\TrailsService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TrailController extends AbstractController
{
    /**
     * @OA\Response(
     *     response="200",
     *     description="Trails list",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(type="object")
     *     )
     * )
     * @OA\Tag(name="Trails")
     * @Route("/trail", name="trail_list", methods={"GET"})
     */
    public function trailsList(TrailsService $trails)
    {
        return $this->json($trails->getTrails());
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail details",
     *     @OA\JsonContent(
     *         type="object"
     *     )
     * )
     * @OA\Parameter(
     *     name="taxonRepo",
     *     in="path",
     *     description="The trail name",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Trails")
     * @Route("/trail/{name}", name="trail_details", methods={"GET"})
     */
    public function trailDetails(TrailsService $trails, string $name)
    {
        return $this->json($trails->getTrail($name));
    }
}
