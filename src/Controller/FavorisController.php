<?php

namespace App\Controller;

use App\Entity\Favoris;
use App\Repository\FavorisRepository;
use App\Service\AnnuaireService;
use App\Service\CreateTrailService;
use App\Service\FavorisService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class FavorisController extends AbstractController
{
    private SerializerInterface $serializer;
    private AnnuaireService $annuaire;
    private FavorisService $favoris;
    private CreateTrailService $createTrail;
    private EntityManagerInterface $em;
    private FavorisRepository $favorisRepository;

    public function __construct(SerializerInterface $serializer, AnnuaireService $annuaire, FavorisService $favoris, CreateTrailService $createTrail, EntityManagerInterface $em, FavorisRepository $favorisRepository)
    {
        $this->serializer = $serializer;
        $this->annuaire = $annuaire;
        $this->favoris = $favoris;
        $this->createTrail = $createTrail;
        $this->em = $em;
        $this->favorisRepository = $favorisRepository;
    }
    /**
     * @OA\Response(
     *     response="200",
     *     description="Get user favorite species",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Favoris::class, groups={"list_favorite"}))
     *     )
     * )
     * @OA\Tag(name="Favoris")
     * @Route("/favoris", name="user_favorite", methods={"GET"})
     */
    public function getFavoris(SerializerInterface $serializer, Request $request, FavorisService $favoris, CreateTrailService $createTrail, AnnuaireService $annuaire): Response
    {
        $user = null;
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->annuaire->getUserInfos($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour afficher votre liste de fiches favorites'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour afficher votre liste de fiches favorites'], Response::HTTP_UNAUTHORIZED);
        }

        $list = $this->favorisRepository->findBy(['user_id' => $user->getId()]);

        $json = $serializer->serialize($list, 'json', ['groups' => 'list_favorite']);

        return new JsonResponse($json, 200, [], true);
    }
}
