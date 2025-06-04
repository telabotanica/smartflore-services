<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Sentier;
use App\Model\CreateTrailDto;
use App\Model\Taxon;
use App\Model\Trail;
use App\Service\AnnuaireService;
use App\Service\BoundingBoxPolygonFactory;
use App\Service\CookieAwareClient;
use App\Service\CreateTrailService;
use App\Service\TrailsService;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Cookie;

class TrailController extends AbstractController
{
    /**
     * @OA\Response(
     *     response="200",
     *     description="Trails list",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Trail::class, groups={"list_trail"}))
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
        $list = $trails->getTrailsList();

        // filter list with given coords bounding box
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

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail details for batch (includes full taxon info)",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Trail::class, groups={"show_trail", "show_taxon", "short_images"})
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
     * @Route("/batch/trail/{id}", name="batch_trail", methods={"GET"})
     */
    public function trailDetailsBatch(
        TrailsService $trails,
        SerializerInterface $serializer,
        $id
    ) {
        if (is_numeric($id)) {
            $id = $trails->getTrailName((int) $id);
        }

        $json = $serializer->serialize(
            $trails->getTrail($id),
            'json', ['groups' => ['show_trail', 'show_taxon', 'short_images']]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @OA\Response(
     *     response="201",
     *     description="Created"
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing trail information",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Sentier::class, groups={"create_trail"})
     *     )
     * )
     * @OA\Tag(name="Trails")
     * @Route("/trail", name="post_trail", methods={"POST"})
     */
    public function createTrail(
        CreateTrailService $createTrail,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        AnnuaireService $annuaire
    ) {
        $content = json_decode($request->getContent());
        $newTrail = $serializer->deserialize($request->getContent(), Sentier::class, 'json', ['groups' => ['create_trail']]);

        // On map les taxons et imaes aux occurrences
        if (count($newTrail->getOccurrences()) > 0) {
            foreach ($newTrail->getOccurrences() as $key => $occurrence) {
                $createTrail->setTaxonToOccurrence($occurrence, $content->occurrences[$key]);
                if (isset($content->occurrences[$key]->image_id)) {
                    $createTrail->setImagesToOccurrence($occurrence, $content->occurrences[$key]);
                }
            }
        }

        $errors = $validator->validate($newTrail);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            return new JsonResponse(['error' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $token = null;
        $cookie = $request->cookies->get($annuaire->getCookieName()) ?? null;
		
		if ($cookie){
			$token = $request->cookies->get($annuaire->getCookieName());
		} else {
			$token = $request->headers->get('Authorization');
		}
		
		$cookie = [
			$annuaire->getCookieName() => $token
		];
	
		['token' => $token, 'error' => $error] = $annuaire->refreshToken($token, $cookie);

        $createTrail->setAuth($token);
        try {
            $trail = $createTrail->process($newTrail);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création du sentier: '. $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_CREATED, [], true);

//        return new JsonResponse('Sentier crée', Response::HTTP_CREATED);
    }
}

