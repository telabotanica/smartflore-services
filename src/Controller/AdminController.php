<?php

namespace App\Controller;

use App\Entity\Sentier;
use App\Repository\SentierRepository;
use App\Service\AnnuaireService;
use App\Service\BoundingBoxPolygonFactory;
use App\Service\CreateTrailService;
use App\Service\SharedService;
use App\Service\TrailsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AdminController extends AbstractController
{
    private SerializerInterface $serializer;
    private EntityManagerInterface $em;
    private SentierRepository $sentierRepository;
    private AnnuaireService $annuaire;
    private CreateTrailService $createTrail;
    private SharedService $sharedService;

    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        SentierRepository $sentierRepository,
        AnnuaireService $annuaire,
        CreateTrailService $createTrail,
        SharedService $sharedService
    )
    {
        $this->serializer = $serializer;
        $this->em = $em;
        $this->sentierRepository = $sentierRepository;
        $this->annuaire = $annuaire;
        $this->createTrail = $createTrail;
        $this->sharedService = $sharedService;
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
     * @OA\Parameter(name="status", in="query", required=false, description="filtre les sentiers par status (Par défaut tous les sentiers sont affichés", @OA\Schema(
     *   type="string",
     *   enum={"En attente","Validé"}
     *  )),
     * @OA\Parameter(name="show_deleted", in="query", required=false,
     *     description="Affiche tous les sentiers (si paramètre absent), seulement les sentiers supprimés (true) ou seulement les sentiers non supprimés (false)",
     *      @OA\Schema(type="boolean")),
     * @OA\Parameter(name="nom", in="query", required=false, description="Nom du sentier", @OA\Schema(type="string", example="Superbes arbres")),
     * @OA\Parameter(name="auteur", in="query", required=false, description="pseudo ou email de l'auteur", @OA\Schema(type="string", example="tela botanica")),
     * @OA\Parameter(name="pmr", in="query", required=false,description="Filtre les sentiers pmr ou ceux dont l'accessibilité est inconnue (1 pour activer le filtre)",
     * @OA\Schema(
     *   type="string",
     *   enum={"-1", "0","1"}
     *  )),
     * @OA\Parameter(name="auteur_id", in="query", required=false, description="id de l'auteur", @OA\Schema(type="string")),
     * @OA\Parameter(name="ordre", in="query", required=false, description="organise les sentiers par nom croissant ou décroissant (ASC par défaut)", @OA\Schema(
     *   type="string",
     *   enum={"ASC","DESC"},
     *   example="DESC"
     *  ))
     * @OA\Tag(name="Admin")
     * @OA\Get(
     *     summary="Get alltrails",
     * )
     * @Route("/admin/trails", name="admin_list_trail", methods={"GET"})
     */
    public function trailsList(
        TrailsService $trails,
        SerializerInterface $serializer,
        Request $request,
        BoundingBoxPolygonFactory $polygonFactory
    ) {
        ['user' => $user, 'token'=> $token, 'error' => $error] = $this->annuaire->getUserFromRequest($request);
        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_UNAUTHORIZED);
        }
        if (!$user || !$token) {
            return new JsonResponse(['error' => 'Erreur d\'authentification, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
        }
        $this->createTrail->setAuth($token);

        if (!$this->annuaire->isAdmin($user)) {
            return new JsonResponse(['error' => 'You need to be an administrator to display this list of trails'], Response::HTTP_FORBIDDEN);
        }

        $searchCriterias = $trails->getSearchCriterias($request);

        $list = $trails->getTrailsList();
        if (!$list || !empty($searchCriterias)) {
            $list = $this->sentierRepository->findByCriterias($searchCriterias);
        }

        $json = $serializer->serialize($list, 'json', ['groups' => 'list_trail']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail published",
     *      @Model(type=Sentier::class, groups={"show_trail"})
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Admin")
     * @OA\Post(
     *     summary="Publish a trail"
     * )
     * @Route("/admin/trail/{id}/publish", name="publish_trail", methods={"POST"})
     */
    public function publishTrail(Request $request, $id): Response
    {
        ['user' => $user, 'token'=> $token, 'error' => $error] = $this->annuaire->getUserFromRequest($request);

        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user || !$token) {
            return new JsonResponse(['error' => 'Erreur d\'authentification, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
        }

        $this->createTrail->setAuth($token);

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if ($trail->getDatePublication() != null) {
            return new JsonResponse(['error' => 'This trail is already published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->annuaire->isAdmin($user)) {
            return new JsonResponse(['error' => 'You need to be an administrator to publish a trail'], Response::HTTP_FORBIDDEN);
        }

        $errors = $this->createTrail->isTrailEligible($trail);
        if ($errors) {
            return new JsonResponse(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $trail->setDatePublication(new \DateTime());
        $trail->setStatus('validé');
        if (!$trail->getDetails()){
            $trail = $this->sharedService->addDetailToTrail($trail);
        }

        $this->em->persist($trail);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail unpublished",
     *      @Model(type=Sentier::class, groups={"show_trail"})
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The trail ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Admin")
     * @OA\Post(
     *     summary="Unpublish a trail"
     * )
     * @Route("/admin/trail/{id}/unpublish", name="unpublish_trail", methods={"POST"})
     */
    public function unPublishTrail(Request $request, $id): Response
    {
        ['user' => $user, 'token'=> $token, 'error' => $error] = $this->annuaire->getUserFromRequest($request);

        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user || !$token) {
            return new JsonResponse(['error' => 'Erreur d\'authentification, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
        }

        $this->createTrail->setAuth($token);

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if ($trail->getDatePublication() == null) {
            return new JsonResponse(['error' => 'This trail is not yet published (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->annuaire->isAdmin($user)) {
            return new JsonResponse(['error' => 'You need to be an administrator to unpublish a trail'], Response::HTTP_FORBIDDEN);
        }

        $trail->setDatePublication(null);
        $trail->setStatus(null);

        $this->em->persist($trail);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }
}
