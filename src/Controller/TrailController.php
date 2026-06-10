<?php

namespace App\Controller;

use Exception;
use DateTime;
use App\Entity\Sentier;
use App\Model\Taxon;
use App\Repository\ImageRepository;
use App\Repository\SentierRepository;
use App\Service\AnnuaireService;
use App\Service\BoundingBoxPolygonFactory;
use App\Service\CacheFileService;
use App\Service\CreateTrailService;
use App\Service\EfloreService;
use App\Service\EmailService;
use App\Service\SharedService;
use App\Service\TrailsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TrailController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly ValidatorInterface $validator, private readonly AnnuaireService $annuaire, private readonly SentierRepository $sentierRepository, private readonly CreateTrailService $createTrail, private readonly EntityManagerInterface $em, private readonly EmailService $emailService, private readonly SharedService $sharedService, private readonly CacheFileService $cacheFile, private readonly TrailsService $trails, private readonly BoundingBoxPolygonFactory $polygonFactory, private readonly ImageRepository $imageRepository, private readonly EfloreService $eflore)
    {
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
     */
    #[Route(path: '/trails', name: 'list_trail', methods: ['GET'])]
    public function trailsList(
        Request $request
    ): JsonResponse {
        $searchCriterias = $this->trails->getSearchCriterias($request);
        $hasBbox = (bool) $request->query->get('bbox');

        // Cache fichier uniquement si pas de critères de recherche et pas de bbox
        if (empty($searchCriterias) && !$hasBbox) {
            $cached = $this->cacheFile->getTrailsList();
            if ($cached !== null) {
                return new JsonResponse(json_encode($cached), Response::HTTP_OK, [], true);
            }
        }

        // Fallback BDD
        $searchCriterias['status'] = 'Validé';
        $searchCriterias['show_deleted'] = false;
        $list = $this->sentierRepository->findByCriterias($searchCriterias);

        if ($hasBbox) {
            $coords = explode(',', $request->query->get('bbox'));
            $list = $this->trails->getTrailsInsideBoundaries(
                $this->polygonFactory->createBoundingBoxPolygon($coords), $list
            );
            $list = $list ?: [];
        }

        $json = $this->serializer->serialize($list, 'json', ['groups' => 'list_trail']);
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
     */
    #[Route(path: '/trail/{id}', name: 'show_trail', methods: ['GET'])]
    public function trailDetails(
        int $id
    ): JsonResponse {
        // --- Lecture cache fichier ---
        $cached = $this->cacheFile->getTrail($id);
        if ($cached !== null) {
            return new JsonResponse($cached, Response::HTTP_OK);
        }

        $trail = $this->sentierRepository->findOneBy(['id' => $id, 'date_suppression' => null]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found or deleted (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        // --- Mise en cache ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        $json = $this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']);

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
     */
    #[Route(path: '/batch/trail/{id}', name: 'batch_trail', methods: ['GET'])]
    public function trailDetailsBatch(
        string $id
    ): JsonResponse {
        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($trail, 'json', ['groups' => ['show_trail', 'show_taxon', 'short_images']]);

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
     */
    #[Route(path: '/trail', name: 'post_trail', methods: ['POST'])]
    public function createTrail(Request $request): Response
    {
        $content = json_decode($request->getContent());
        if ($content === null) {
            return new JsonResponse(['error' => 'Corps de la requête JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $newTrail = $this->serializer->deserialize($request->getContent(), Sentier::class, 'json', ['groups' => ['create_trail']]);

        // On map les taxons et images aux occurrences
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

        if (isset($content->image)){
            $newImage = $this->createTrail->getImageFromContent($content->image);
            $this->em->persist($newImage);
            $this->em->flush();

            $newTrail->setImage($newImage);
        }

        try {
            $trail = $this->createTrail->process($newTrail);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la création du sentier: '. $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        // --- Mise en cache après création ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

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
     */
    #[Route(path: '/trail/{id}', name: 'update_trail', methods: ['PUT'])]
    public function updateTrail(Request $request, string $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
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

        $trail->setPathLength((int) round(TrailsService::getTrailLength($trail)));
        $trail->setDateModification(new DateTime());

        if (isset($content->image)){
            $newImage = $this->createTrail->getImageFromContent($content->image);
            $this->em->persist($newImage);
            $this->em->flush();

            $trail->setImage($newImage);
        }

        if (!$trail->getDetails()){
            $trail = $this->sharedService->addDetailToTrail($trail);
        }

        $this->em->persist($trail);
        $this->em->flush();

        // --- Mise à jour du cache après modification ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

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
     */
    #[Route(path: '/trail/{id}', name: 'delete_trail', methods: ['DELETE'])]
    public function deleteTrail(Request $request, int $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour du sentier: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateTrail($user, $trail)) {
            return new JsonResponse(['error' => 'You are not allowed to update this trail (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $trail->setDateSuppression(new DateTime());
        $this->em->persist($trail);
        $this->em->flush();

        // --- Suppression du cache après soft-delete ---
        $this->cacheFile->deleteTrail($id);

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
     */
    #[Route(path: '/trail/{id}/review', name: 'review_trail', methods: ['POST'])]
    public function reviewTrail(Request $request, string $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
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

        // --- Mise à jour du cache après changement de statut ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        $displayName = $trail->getAuteurEmail() ?? $trail->getAuteur();
        $admins = $this->annuaire->listAdmin();
        $url = $this->sharedService->getSentierFrontUrl($trail);

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
            } catch (Exception $e) {
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
     * @OA\RequestBody(
     *      description="A JSON object containing trail information.",
     *      required=true,
     *      @OA\JsonContent(
     *          type="object",
     *          ref=@Model(type=Sentier::class, groups={"update_image"})
     *      )
     *  )
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
     */
    #[Route(path: '/trail/{id}/update-image', name: 'update_trail_image', methods: ['PUT'])]
    public function updateTrailImage(Request $request, string $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour du sentier: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $content = json_decode($request->getContent());

        if (!$content || !isset($content->image)) {
            return new JsonResponse(['error' => 'No image id provided'], Response::HTTP_BAD_REQUEST);
        }

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateTrail($user, $trail)) {
            return new JsonResponse(['error' => 'You are not allowed to update this trail (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $newImage = $this->createTrail->getImageFromContent($content->image);
        $this->em->persist($newImage);
        $this->em->flush();

        $trail->setImage($newImage);

        $this->em->persist($trail);
        $this->em->flush();

        // --- Mise à jour du cache ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="deleted",
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
     * @OA\Put(
     *     summary="delete a trail default image"
     * )
     */
    #[Route(path: '/trail/{id}/delete-image', name: 'delete_trail_image', methods: ['DELETE'])]
    public function deleteTrailImage(Request $request, string $id): Response
    {
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $this->createTrail->setAuth($token);
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour du sentier: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail || !$trail->getImage()) {
            return new JsonResponse(['error' => 'Trail not found or no image set on this trail (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->annuaire->canUpdateTrail($user, $trail)) {
            return new JsonResponse(['error' => 'You are not allowed to update this trail (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        $image = $this->imageRepository->findOneBy(['id' => $trail->getImage()->getId()]);
        if (!$image) {
            return new JsonResponse(['error' => 'Image not found (id: '. $trail->getImage()->getId() .')'], Response::HTTP_NOT_FOUND);
        }

        $this->imageRepository->remove($image);

        $trail->setImage(null);

        $this->em->persist($trail);
        $this->em->flush();

        // --- Mise à jour du cache ---
        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

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
     */
    #[Route(path: '/trail/{id}/check', name: 'check_trail', methods: ['GET'])]
    public function checkTrail(string $id): Response
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
     */
    #[Route(path: '/trail/{id}/taxons', name: 'trail_taxons', methods: ['GET'])]
    public function trailTaxons(
        int $id
    ): Response {
        $cached = $this->cacheFile->getTrail($id);
        $trail = null;
        if ($cached !== null) {
            try {
                $trail = $this->serializer->deserialize(
                    json_encode($cached),
                    Sentier::class,
                    'json',
                    ['groups' => 'show_trail']
                );
            } catch (Exception) {
                $trail = null;
            }
        }

        if ($trail === null) {
            $trail = $this->sentierRepository->findOneBy(['id' => $id, 'date_suppression' => null]);
        }

        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found or deleted (id: ' . $id . ')'], Response::HTTP_NOT_FOUND);
        }

        $taxonsData = $this->createTrail->getUniqueTaxons($trail);

        $taxons = [];
        foreach ($taxonsData as $taxonData) {
            try {
                $cachedTaxon = $this->cacheFile->getTaxon($taxonData['taxon_repository'], $taxonData['name_id']);
                if ($cachedTaxon !== null) {
                    $taxon = $this->serializer->deserialize(json_encode($cachedTaxon), Taxon::class, 'json', ['groups' => 'show_taxon']);
                } else {
                    $taxon = $this->eflore->getTaxon(
                        $taxonData['taxon_repository'],
                        $taxonData['name_id'],
                        true
                    );
                    $this->cacheFile->saveTaxon($taxonData['taxon_repository'], $taxonData['name_id'], $taxon, ['show_taxon', 'full_images']);
                }

                if ($taxon) {
                    $taxons[] = $taxon;
                }
            } catch (Exception) {
                $taxons[] = $taxonData;
            }
        }

        $json = $this->serializer->serialize($taxons, 'json', ['groups' => ['show_taxon', 'full_images']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}