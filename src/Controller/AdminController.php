<?php

namespace App\Controller;

use App\Entity\Sentier;
use App\Repository\SentierRepository;
use App\Service\AnnuaireService;
use App\Service\BoundingBoxPolygonFactory;
use App\Service\CacheFileService;
use App\Service\CreateTrailService;
use App\Service\EmailService;
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
    private EmailService $emailService;
    private CacheFileService $cacheFile;
    private TrailsService $trailsService;
    /**
     * @var \App\Service\TrailsService
     */
    private $trails;
    /**
     * @var \App\Service\BoundingBoxPolygonFactory
     */
    private $polygonFactory;

    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        SentierRepository $sentierRepository,
        AnnuaireService $annuaire,
        CreateTrailService $createTrail,
        SharedService $sharedService,
        EmailService $emailService,
        CacheFileService $cacheFile,
        TrailsService $trailsService,
        \App\Service\TrailsService $trails,
        \App\Service\BoundingBoxPolygonFactory $polygonFactory
    )
    {
        $this->serializer = $serializer;
        $this->em = $em;
        $this->sentierRepository = $sentierRepository;
        $this->annuaire = $annuaire;
        $this->createTrail = $createTrail;
        $this->sharedService = $sharedService;
        $this->emailService = $emailService;
        $this->cacheFile = $cacheFile;
        $this->trailsService = $trailsService;
        $this->trails = $trails;
        $this->polygonFactory = $polygonFactory;
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
        Request $request
    ): \Symfony\Component\HttpFoundation\JsonResponse {
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

        $searchCriterias = $this->trails->getSearchCriterias($request);

        $list = $this->sentierRepository->findByCriterias($searchCriterias);

        $json = $this->serializer->serialize($list, 'json', ['groups' => 'list_trail']);

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
    public function publishTrail(Request $request, string $id): Response
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

        if ($trail->getStatus() != 'En attente') {
            return new JsonResponse(['error' => 'This trail is not waiting admin approval (id: '. $id .', status = '.$trail->getStatus().')'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->annuaire->isAdmin($user)) {
            return new JsonResponse(['error' => 'You need to be an administrator to publish a trail'], Response::HTTP_FORBIDDEN);
        }

        $errors = $this->createTrail->isTrailEligible($trail);
        if ($errors) {
            return new JsonResponse(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $trail->setDatePublication(new \DateTime());
        $trail->setStatus('Validé');
        if (!$trail->getDetails()){
            $trail = $this->sharedService->addDetailToTrail($trail);
        }

        $this->em->persist($trail);
        $this->em->flush();

        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);
        $this->trailsService->rebuildTrailsList();

        if ($trail->getAuteurEmail()) {
            $url = "https://www.tela-botanica.org/proposer-une-actualite/";
//            $urlCharte = "https://www.tela-botanica.org/wikini/smartflore/wakka.php?wiki=PageCharte&Authorization";
            try {
                $message = '
                <p>Bonjour,</p>
                <p>Merci pour l’intérêt que vous portez à Smart\'Flore !</p>
                <p>Nous avons bien reçu la demande de validation du sentier Smart\'Flore : " <b>' . $trail->getNom() . '</b>", et nous l\'avons validé. 
                Il sera visible sur l\'application d\'ici 24h maximum.</p>
                <p>N’hésitez pas à publier un article sur le site web de Tela Botanica pour valoriser votre sentier auprès du réseau ou à publier un événement si vous prévoyez une inauguration du sentier par exemple. 
                Voici le lien pour proposer une publication : <a href="' . $url . '">' . $url . '</a></p>
                <p>Bonne journée,</br>
                L\'équipe Smart\'Flore</p>
                ';

                $this->emailService->sendEmail(
                    'contact-smartflore@tela-botanica.org',
                    $trail->getAuteurEmail(),
                    "Votre sentier Smart'Flore a été validé",
                    $message,
                    'contact-smartflore@tela-botanica.org'
                );
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

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
    public function unPublishTrail(Request $request, string $id): Response
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

        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        $admins = $this->annuaire->listAdmin();
        $url = $this->sharedService->getSentierFrontUrl($trail);
        foreach ($admins as $admin) {
            try {
                $message = '
                    <h1>Sentier dépublié</h1>
                    <p>Bonjour,</p>
                    <p>Vous recevez ce message car vous êtes administrateur des sentiers SmartFlore</p>
                    <p>Le sentier Smart\'Flore : " <a href="' . $url . '"><b>' . $trail->getNom() . '</b></a>", a été dépublié</p>
                    <p>Merci d\'envoyer un message à l\'utilisateur (<a href="mailto:' . $trail->getAuteurEmail() . '">' . $trail->getAuteurEmail() . '</a>) afin de lui en expliquer la raison</p>
                    <p>Bonne journée,</br>
                    L\'équipe Smart\'Flore</p>
                ';

                $this->emailService->sendEmail(
                    'telaorg@tela-botanica.org',
                    $admin,
                    "Sentier Smart'Flore dépublié",
                    $message,
                    'contact-smartflore@tela-botanica.org'
                );
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail rejected",
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
     *     summary="Reject a trail waiting admin approval"
     * )
     * @Route("/admin/trail/{id}/reject", name="reject_trail", methods={"POST"})
     */
    public function rejectTrail(Request $request, string $id): Response
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

        if ($trail->getStatus() != 'En attente') {
            return new JsonResponse(['error' => 'This trail is not waiting admin approval (id: '. $id .', status = '.$trail->getStatus().')'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->annuaire->isAdmin($user)) {
            return new JsonResponse(['error' => 'You need to be an administrator to unpublish a trail'], Response::HTTP_FORBIDDEN);
        }

        $trail->setStatus(null);

        $this->em->persist($trail);
        $this->em->flush();

        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        $admins = $this->annuaire->listAdmin();
        $url = $this->sharedService->getSentierFrontUrl($trail);
        foreach ($admins as $admin) {
            try {
                $message = '
            <h1>Sentier refusé</h1>
            <p>Bonjour,</p>
            <p>Vous recevez ce message car vous êtes administrateur des sentiers SmartFlore</p>
            <p>Nous avons reçu une demande de validation du sentier Smart\'Flore : " <a href="' . $url . '"><b>' . $trail->getNom() . '</b></a>", et nous l\'avons rejeté</p>
            <p>Merci d\'envoyer un message à l\'utilisateur (<a href="mailto:' . $trail->getAuteurEmail() . '">' . $trail->getAuteurEmail() . '</a>) afin de lui en expliquer la raison</p>
            <p>Bonne journée,</br>
            L\'équipe Smart\'Flore</p>
            ';

                $this->emailService->sendEmail(
                    'telaorg@tela-botanica.org',
                    $admin,
                    "Sentier Smart'Flore refusé",
                    $message,
                    'contact-smartflore@tela-botanica.org'
                );
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Trail reactivated",
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
     *     summary="Reactivate a deleted trail"
     * )
     * @Route("/admin/trail/{id}/reactivate", name="reactivate_trail", methods={"POST"})
     */
    public function reactivateTrail(Request $request, string $id): Response
    {
        ['user' => $user, 'token' => $token, 'error' => $error] = $this->annuaire->getUserFromRequest($request);

        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user || !$token) {
            return new JsonResponse(['error' => 'Erreur d\'authentification, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
        }

        $trail = $this->sentierRepository->findOneBy(['id' => $id]);
        if (!$trail) {
            return new JsonResponse(['error' => 'Trail not found (id: '. $id .')'], Response::HTTP_NOT_FOUND);
        }

        if ($trail->getDateSuppression() == null) {
            return new JsonResponse(['error' => 'This trail is not deleted (id: '. $id .')'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->annuaire->isAdmin($user)) {
            return new JsonResponse(['error' => 'You need to be an administrator to reactivate a trail'], Response::HTTP_FORBIDDEN);
        }

        $trail->setDateSuppression(null);
        $this->em->persist($trail);
        $this->em->flush();

        $this->cacheFile->saveTrail($trail->getId(), $trail, ['show_trail']);

        return new JsonResponse($this->serializer->serialize($trail, 'json', ['groups' => 'show_trail']), Response::HTTP_OK, [], true);

    }
}
