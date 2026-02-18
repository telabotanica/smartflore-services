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
use App\Service\EfloreService;
use App\Service\EmailService;
use App\Service\ImageService;
use App\Service\SharedService;
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
    private EmailService $emailService;
    private SharedService $sharedService;
    private ImageService $imageService;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, AnnuaireService $annuaire, SentierRepository $sentierRepository, CreateTrailService $createTrail, EntityManagerInterface $em, EmailService $emailService, SharedService $sharedService, ImageService $imageService)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->annuaire = $annuaire;
        $this->sentierRepository = $sentierRepository;
        $this->createTrail = $createTrail;
        $this->em = $em;
        $this->emailService = $emailService;
        $this->sharedService = $sharedService;
        $this->imageService = $imageService;
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
     * ),
     * @OA\Parameter(name="nom", in="query", required=false, description="Nom du sentier", @OA\Schema(type="string", example="Superbes arbres")),
     * @OA\Parameter(name="auteur", in="query", required=false, description="pseudo ou email de l'auteur", @OA\Schema(type="string", example="tela botanica")),
     * @OA\Parameter(name="pmr", in="query", required=false,description="Filtre les sentiers pmr ou ceux dont l'accessibilité est inconnue (1 pour activer le filtre)",
     * @OA\Schema(
     *   type="string",
     *   enum={"-1", "0","1"},
     *   example="1"
     *  )),
     * @OA\Parameter(name="auteur_id", in="query", required=false, description="id de l'auteur", @OA\Schema(type="string", example="10000")),
     * @OA\Parameter(name="ordre", in="query", required=false, description="organise les sentiers par nom croissant ou décroissant (ASC par défaut)", @OA\Schema(
     *   type="string",
     *   enum={"ASC","DESC"},
     *   example="DESC"
     *  ))
     * @OA\Tag(name="Trails")
     * @OA\Get(
     *     summary="Get all published trails (public)",
     * )
     * @Route("/trails", name="list_trail", methods={"GET"})
     */
    public function trailsList(
        TrailsService $trails,
        SerializerInterface $serializer,
        Request $request,
        BoundingBoxPolygonFactory $polygonFactory
    ) {
        $searchCriterias = $trails->getSearchCriterias($request);

        $list = $trails->getTrailsList();
        if (!$list || !empty($searchCriterias)) {
            $searchCriterias['status'] = 'Validé';
            $searchCriterias['show_deleted'] = false;
            $list = $this->sentierRepository->findByCriterias($searchCriterias);
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
     * @OA\Get(
     *     summary="Get a trail (public)",
     * )
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
        if (!$token) {
            return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
        }
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
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
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

        // On empêche les modifications d'un sentier une fois celui-ci publié
        if ($trail->getDatePublication() != null || $trail->getStatus() == 'Validé') {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $content = json_decode($request->getContent());
        if (!$request->getContent()) {
            return new JsonResponse(['error' => 'No update requested on trail (id: '. $id .')'], Response::HTTP_BAD_REQUEST);
        }

        $trail = $this->serializer->deserialize(json_encode($content), Sentier::class, 'json', ['groups' => 'update_trail', 'object_to_populate' => $trail]);

        $trail->setPathLength(round(TrailsService::getTrailLength($trail)));
        $trail->setDateModification(new \DateTime());

        if (!$trail->getDetails()){
            $trail = $this->sharedService->addDetailToTrail($trail);
        }

        $this->em->persist($trail);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="202",
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

    /**
     * @OA\Response(
     *     response="200",
     *     description="send trail to review",
     *      @Model(type=Sentier::class, groups={"show_trail"})
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Trails")
     * @OA\Post(
     *     summary="send trail to review"
     * )
     * @Route("/trail/{id}/review", name="review_trail", methods={"POST"})
     */
    public function reviewTrail(Request $request, $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
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

        // On empêche les modifications d'un sentier une fois celui-ci publié
        if ($trail->getDatePublication() != null) {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $errors = $this->createTrail->isTrailEligible($trail);
        if ($errors) {
            return new JsonResponse(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $trail->setStatus('En attente');

        $this->em->persist($trail);
        $this->em->flush();

        $displayName = $trail->getAuteurEmail() ?? $trail->getAuteur();
        $admins = $this->annuaire->listAdmin();
        $url = "https://www.tela-botanica.org/appli:smartflore" . "/trail/" . $trail->getId();

        foreach ($admins as $admin) {
            try {
                $message= '
            <h1>Sentier en attente de validation</h1>
            <p>Bonjour,<br/>vous recevez ce message car vous êtes administrateur des sentiers SmartFlore.</p>
            <p>Un nouveau sentier requiert votre attention : </p>
            <p>Nom du sentier : <b>'.$trail->getNom().'</b></br>
            Auteur du sentier : '.$displayName.'</p>
            <p>Rendez-vous sur le site <a href="'.$url.'">'.$url.'</a> pour consulter le sentier.</p>
            ';

            $this->emailService->sendEmail(
                'telaorg@tela-botanica.org',
                $admin,
                "Demande de validation d'un sentier",
                $message
            );
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'envoi de l\'email: '. $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="updated",
     *      @Model(type=Sentier::class, groups={"show_trail"})
     * )
     * @OA\Parameter(name="image_id", in="query", required=true, description="Cel image id", @OA\Schema(type="string", example="10023")),
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Trails")
     * @OA\Put(
     *     summary="update a trail default image"
     * )
     * @Route("/trail/{id}/update-image", name="update_trail_image", methods={"PUT"})
     */
    public function updateTrailImage(Request $request, $id): Response
    {
        $newImage = $request->query->get('image_id');
        if (!$newImage) {
            return new JsonResponse(['error' => 'No image id provided'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
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

        $newImage = $this->imageService->findImageFromId($newImage);
        $trail->setImage($newImage);

        $this->em->persist($trail);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Check if trail can be published",
     *      @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="status", type="string", example="OK"),
     *         @OA\Property(
     *            property="error",
     *            type="array",
     *            @OA\Items(type="string", example="")
     *            )
     *        )
     *     ),
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Trails")
     * @OA\Get(
     *     summary="Check if trail can be published"
     * )
     * @Route("/trail/{id}/check", name="check_trail", methods={"GET"})
     */
    public function checkTrail(Request $request, $id): Response
    {
        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if ($trail->getDatePublication() != null) {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $errors = $this->createTrail->isTrailEligible($trail);
        if ($errors) {
            return new JsonResponse(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => true, 'error' => $errors], Response::HTTP_OK);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Unique taxons list for a trail",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Taxon::class, groups={"show_taxon"}))
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
     * @OA\Get(
     *     summary="Get unique taxons for a trail"
     * )
     * @Route("/trail/{id}/taxons", name="trail_taxons", methods={"GET"})
     */
    public function trailTaxons(
        EfloreService $eflore,
        SerializerInterface $serializer,
        $id
    ): Response {
        $trail = $this->sentierRepository->findOneBy(['id' => $id, 'date_suppression' => null]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found or deleted (id: ' . $id . ')'], Response::HTTP_NOT_FOUND);
        }

        $taxonsData = $this->createTrail->getUniqueTaxons($trail);

        $taxons = [];
        foreach ($taxonsData as $taxonData) {
            try {
                $taxon = $eflore->getTaxon(
                    $taxonData['taxon_repository'],
                    $taxonData['name_id'],
                    true
                );
                if ($taxon) {
                    $taxons[] = $taxon;
                }
            } catch (\Exception $e) {
                $taxons[] = $taxonData;
            }

        }

        $json = $serializer->serialize($taxons, 'json', ['groups' => ['show_taxon', 'full_images']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}

