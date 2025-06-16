<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Sentier;
use App\Model\CreateTrailDto;
use App\Model\Taxon;
use App\Model\Trail;
use App\Repository\SentierRepository;
use App\Service\AnnuaireService;
use App\Service\BoundingBoxPolygonFactory;
use App\Service\CookieAwareClient;
use App\Service\CreateTrailService;
use App\Service\TrailsService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private AnnuaireService $annuaire;
    private SentierRepository $sentierRepository;
    private CreateTrailService $createTrail;
    private EntityManagerInterface $em;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, AnnuaireService $annuaire, SentierRepository $sentierRepository, CreateTrailService $createTrail, EntityManagerInterface $em)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->annuaire = $annuaire;
        $this->sentierRepository = $sentierRepository;
        $this->createTrail = $createTrail;
        $this->em = $em;
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trails list",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Sentier::class, groups={"list_trail"}))
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
        if (!$list){
            $list = $this->sentierRepository->findBy(['status' => 'Validé', 'date_suppression' => null], ['nom' => 'ASC']);
        }

        // filter list with given coords bounding box
        if ($bbox = $request->query->get('bbox')) {
            // we need two coordinates to build a bounding box: northEast and southWest
            $coords = explode(',', $bbox);
            $list = $trails->getTrailsInsideBoundaries(
                $polygonFactory->createBoundingBoxPolygon($coords), $list
            );

            // fallback si rien trouvé dans la bbox
            if (!$list) {
                $list = [];
            }
        }

        $json = $serializer->serialize($list, 'json', ['groups' => 'list_trail']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail details",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Sentier::class, groups={"show_trail"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
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
        $trail = $this->sentierRepository->findOneBy(['id' => $id, 'date_suppression' => null]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found or deleted (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

//        $json = $serializer->serialize($trails->getTrail($id), 'json', ['groups' => 'show_trail']);

        $json = $serializer->serialize($trail, 'json', ['groups' => 'show_trail']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail details for batch (includes full taxon info)",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Sentier::class, groups={"show_trail", "show_taxon", "short_images"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
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
        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        $json = $serializer->serialize($trail, 'json', ['groups' => ['show_trail', 'show_taxon', 'short_images']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="201",
     *     description="Created",
     *     @Model(type=Sentier::class, groups={"show_trail"})
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
    public function createTrail(Request $request): Response
    {
        $content = json_decode($request->getContent());
        $newTrail = $this->serializer->deserialize($request->getContent(), Sentier::class, 'json', ['groups' => ['create_trail']]);

        // On map les taxons et imaes aux occurrences
        if (count($newTrail->getOccurrences()) > 0) {
            foreach ($newTrail->getOccurrences() as $key => $occurrence) {
                $this->createTrail->setTaxonToOccurrence($occurrence, $content->occurrences[$key]);
                if (isset($content->occurrences[$key]->image_id)) {
                    $this->createTrail->setImagesToOccurrence($occurrence, $content->occurrences[$key]->image_id);
                }
            }
        }

        $errors = $this->validator->validate($newTrail);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            return new JsonResponse(['error' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $token = $this->annuaire->getRequestToken($request);
        $this->createTrail->setAuth($token);

        try {
            $trail = $this->createTrail->process($newTrail);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création du sentier: '. $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_CREATED, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="updated",
     *      @Model(type=Sentier::class, groups={"show_trail"})
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing trail information",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Sentier::class, groups={"update_trail"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Trails")
     * @OA\Put(
     *     summary="update a trail localisation, name, path, prm access or best seasons information"
     * )
     * @Route("/trail/{id}", name="update_trail", methods={"PUT"})
     */
    public function updateTrail(Request $request, $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour du sentier: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateTrail($user, $trail)) {
            return new JsonResponse(['error' => 'You are not allowed to update this trail (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        // On empêche les modification d'un sentier une fois celui-ci publié
        if ($trail->getDatePublication() != null) {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $content = json_decode($request->getContent());
        if (!$request->getContent()) {
            return new JsonResponse(['error' => 'No update requested on trail (id: '. $id .')'], Response::HTTP_BAD_REQUEST);
        }

        $trail = $this->serializer->deserialize(json_encode($content), Sentier::class, 'json', ['groups' => 'update_trail', 'object_to_populate' => $trail]);

        $trail->setPathLength(round(TrailsService::getTrailLength($trail)));
        $trail->setDateModification(new \DateTime());

        $this->em->persist($trail);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="deleted",
     *      @OA\JsonContent(
     *         type="string",
     *        example="Trail id: 146 deleted"
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Trails")
     * @OA\Delete(
     *     summary="Delete a trail",
     * )
     * @Route("/trail/{id}", name="delete_trail", methods={"DELETE"})
     */
    public function deleteTrail(Request $request, $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour du sentier: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateTrail($user, $trail)) {
            return new JsonResponse(['error' => 'You are not allowed to update this trail (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $trail->setDateSuppression(new \DateTime());
        $this->em->persist($trail);
        $this->em->flush();

        return new JsonResponse('Trail id: '.$id.' deleted', Response::HTTP_ACCEPTED);
    }
}

