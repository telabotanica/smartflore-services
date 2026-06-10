<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Exception;
use App\Entity\Favoris;
use App\Repository\FavorisRepository;
use App\Service\AnnuaireService;
use App\Service\CreateTrailService;
use App\Service\EfloreService;
use App\Service\FavorisService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class FavorisController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly AnnuaireService $annuaire, private readonly FavorisService $favoris, private readonly CreateTrailService $createTrail, private readonly EntityManagerInterface $em, private readonly FavorisRepository $favorisRepository, private readonly EfloreService $eflore)
    {
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
     * @OA\Get(
     *     summary="Get user favorite species"
     * )
     */
    #[Route(path: '/favoris', name: 'user_favorite', methods: ['GET'])]
    public function getFavoris(Request $request): Response
    {
        $user = null;
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour afficher votre liste de fiches favorites'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour afficher votre liste de fiches favorites'], Response::HTTP_UNAUTHORIZED);
        }

        $list = $this->favorisRepository->findBy(['user_id' => $user->getId()]);

        $json = $this->serializer->serialize($list, 'json', ['groups' => 'list_favorite']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Add favorite species",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Favoris::class, groups={"list_favorite"}))
     *     )
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing taxon information",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Favoris::class, groups={"add_favorite"})
     *     )
     * )
     * @OA\Tag(name="Favoris")
     * @OA\Post(
     *     summary="Add a taxon to the favorite list"
     * )
     */
    #[Route(path: '/favoris', name: 'add_favorite', methods: ['POST'])]
    public function addFavoris(Request $request): Response
    {
        $user = null;
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour ajouter une fiche en favoris'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour ajouter une fiche en favoris'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$request->getContent()) {
            return new JsonResponse(['error' => 'No data available in order to add a taxon to the favorite list'], Response::HTTP_BAD_REQUEST);
        }

        $content = json_decode($request->getContent());
        if (!isset($content->taxon_id) || !isset($content->referentiel)) {
            return new JsonResponse(['error' => 'taxon_id and referentiel are mandatory in order to add a taxon to the favorite list'], Response::HTTP_BAD_REQUEST);
        }

        $favoris = $this->serializer->deserialize($request->getContent(), Favoris::class, 'json', ['groups' => ['add_favorite']]);
        $favoris->setUserId($user->getId());
        $favoris->setUserEmail($user->getEmail());

        $existingFavoris = $this->favoris->checkExistingFavoris($favoris->getReferentiel(), $favoris->getTaxonId(), $user->getId());
        if ($existingFavoris) {
            return new JsonResponse(['error' => 'This taxon is already in your favorite list'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $taxon = $this->eflore->getTaxonRawInfo($favoris->getReferentiel(), $favoris->getTaxonId());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération du taxon . Veuillez vérifier le référentiel '.$favoris->getReferentiel() .'et le taxon id: taxon_id='.$favoris->getTaxonId().'.: message='.$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $scientific_name = $taxon['nom_sci'];
        $favoris->setScientificName($scientific_name);

        $this->em->persist($favoris);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($favoris, 'json', ['groups' => 'show_favorite']), Response::HTTP_CREATED, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="deleted",
     *      @OA\JsonContent(
     *          type="string",
     *          example="Favorite deleted (id: 146)"
     *      )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The favorite ID",
     *     @OA\Schema(type="integer"),
     *     example=146
     * )
     * @OA\Tag(name="Favoris")
     * @OA\Delete(
     *     summary="Remove a taxon from the favorite list"
     * )
     */
    #[Route(path: '/favoris/{id}', name: 'delete_favoris', methods: ['DELETE'])]
    public function deleteOccurrence(Request $request, string $id): Response
    {
        $user = null;
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour ajouter une fiche en favoris'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user) {
            return new JsonResponse(['error' => 'Veuillez vous connecter pour ajouter une fiche en favoris'], Response::HTTP_UNAUTHORIZED);
        }

        $favoris = $this->favorisRepository->findOneBy(['id' => $id, 'user_id' => $user->getId()]);
        if (!$favoris) {
            return new JsonResponse(['error' => 'Favorite not found (id: '. $id .') or favorite not belonging to user '.$user->getEmail()], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($favoris);
        $this->em->flush();

        return new JsonResponse('Favorite deleted (id: '. $id .', nom: '.$favoris->getScientificName().', taxon_id: '.$favoris->getTaxonId().', referentiel: '.$favoris->getReferentiel().')', Response::HTTP_OK);
    }
}
