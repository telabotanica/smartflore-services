<?php

namespace App\Controller;

use App\Model\Trail;
use App\Service\BoundingBoxPolygonFactory;
use App\Service\TrailsService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class TrailController extends AbstractController
{
    /**
     * @OA\Response(
     *     response="200",
     *     description="Trails list",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Trail::class, groups={"show_trail"}))
     *     ),
     * )
     * @OA\Parameter(
     *     name="bbox",
     *     in="query",
     *     description="Bounding box's upper-left and lower-right coordinates",
     *     @OA\Schema(type="string"),
     *     example="90.0,179.0,-90.0,-172.0"
     * )
     * @OA\Tag(name="Trails")
     * @Route("/trails", name="list_trail", methods={"GET"})
     */
    public function trailsList(
        TrailsService $trails,
        SerializerInterface $serializer,
        Request $request,
        BoundingBoxPolygonFactory $polygonFactory
    ) {
        $list = $trails->getTrails();

        if ($bbox = $request->query->get('bbox')) {
            // we need two coordinates to build a bounding box: northEast and southWest
            $coords = explode(',', $bbox);

            $list = $trails->getTrailsInsideBoundaries(
                $polygonFactory->createBoundingBoxPolygon($coords)
            );
        }

        // alphabetical sort
        $coll = collator_create('fr_FR');
        usort($list, static function(Trail $a, Trail $b) use ($coll) {
            return collator_compare($coll, mb_strtolower($a->getDisplayName()), mb_strtolower($b->getDisplayName()));
        });

        $json = $serializer->serialize($list, 'json', ['groups' => 'list_trail']);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail details",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Trail::class, groups={"show_trail"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID (or trail name string)",
     *     @OA\Schema(type="integer"),
     *     example="146"
     * )
     * @OA\Tag(name="Trails")
     * @Route("/trail/{id}", name="show_trail", methods={"GET"})
     */
    public function trailDetails(
        TrailsService $trails,
        SerializerInterface $serializer,
        $id
    ) {
        if (is_numeric($id)) {
            $id = $trails->getTrailName((int) $id);
        }

        $json = $serializer->serialize($trails->getTrail($id), 'json', ['groups' => 'show_trail']);

        return new JsonResponse($json, 200, [], true);
    }
}

