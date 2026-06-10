<?php

namespace App\Controller;

use Exception;
use DateTime;
use App\Entity\Fiche;
use App\Repository\FicheRepository;
use App\Repository\OccurrenceRepository;
use App\Service\AnnuaireService;
use App\Service\CacheFileService;
use App\Service\FicheService;
use App\Service\SharedService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class FicheController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly EntityManagerInterface $em, private readonly FicheRepository $ficheRepository, private readonly OccurrenceRepository $occurrenceRepository, private readonly FicheService $ficheService, private readonly SharedService $sharedService, private readonly AnnuaireService $annuaire, private readonly CacheFileService $cacheFile)
    {
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Fiches list",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Fiche::class, groups={"list_fiche"})
     *     ),
     * )
     * @OA\Parameter(
     *     name="referentiel",
     *     in="path",
     *     description="The taxon repository code (""référentiel"")",
     *     example="bdtfx",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="num_tax",
     *     in="path",
     *     description="The taxonomic name id (""num tax"")",
     *     example="8522",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Fiches")
     * @OA\Get(
     *     summary="Get one page (public)",
     * )
     */
    #[Route(path: '/fiche/{referentiel}/{num_tax}', name: 'single_fiche', methods: ['GET'])]
    public function getFiche(string $referentiel, int $num_tax): Response
    {
        // --- Lecture cache fichier ---
        $cached = $this->cacheFile->getFiche($referentiel, $num_tax);
        if ($cached !== null) {
            return new JsonResponse($cached, Response::HTTP_OK);
        }

        $fiche = $this->sharedService->chercherFiche($referentiel, $num_tax);

        if (!$fiche) {
            return new JsonResponse(['error' => 'Fiche not found (referentiel: '. $referentiel .', num_tax: '. $num_tax .')'], Response::HTTP_NOT_FOUND);
        }

        // --- Mise en cache ---
        $this->cacheFile->saveFiche($referentiel, $num_tax, $fiche, ['list_fiche']);

        $json = $this->serializer->serialize($fiche, 'json', ['groups' => ['list_fiche']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Fiches list",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Fiche::class, groups={"list_fiche"})
     *     ),
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing page information",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Fiche::class, groups={"update_fiche"})
     *     )
     * )
     * @OA\Tag(name="Fiches")
     * @OA\Put(
     *     summary="Update page",
     * )
     */
    #[Route(path: '/fiche/{referentiel}/{num_tax}', name: 'update_fiche', methods: ['PUT'])]
    public function updateFiche(string $referentiel, int $num_tax, Request $request): Response
    {
        $user = null;
        try {
            $token = $this->annuaire->getRequestToken($request);
            if (!$token) {
                return new JsonResponse(['error' => 'No token found, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->annuaire->getUserInfos($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur d\'authentification lors de la mise à jour de la fiche: '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $fiche = $this->sharedService->chercherFiche($referentiel, $num_tax);

        if (!$fiche) {
            return new JsonResponse(['error' => 'Fiche not found (referentiel: '. $referentiel .', num_tax: '. $num_tax .')'], Response::HTTP_NOT_FOUND);
        }

        $content = json_decode($request->getContent());
        if (!$request->getContent()) {
            return new JsonResponse(['error' => 'No update requested on page (id: '. $fiche->getTag() .')'], Response::HTTP_BAD_REQUEST);
        }

        $fiche->setDerniereVersion(false);

        $newFiche = clone $fiche;
        $newFiche = $this->serializer->deserialize(json_encode($content), Fiche::class, 'json', ['groups' => ['update_fiche'],  'object_to_populate' => $newFiche]);

        if (empty(trim((string) $newFiche->getDescription()))) {
            return new JsonResponse(['error' => 'A description is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $newFiche->setProprietaire($user->getName() ?? 'anonyme');
            $newFiche->setUser($user->getId());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la maj du propriétaire de la fiche: '. $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $newFiche->setDerniereVersion(true);
        $newFiche->setDateModification(new DateTime());
        $newFiche->setTag($this->sharedService->formaterPageNom($newFiche->getReferentiel(), $newFiche->getNt()));

        $occurrences = $this->occurrenceRepository->findByTaxon($newFiche->getReferentiel(), $newFiche->getNt());
        foreach ($occurrences as $occurrence) {
            $taxon = $occurrence->getTaxon(); //"accepted_scientific_name_id"
            $taxon['tabs'] = $newFiche->getTag();

            $occurrence->setTaxon($taxon);
            $occurrence->setCardTag($newFiche->getTag());

            // --- Invalidation du cache taxon et sentier lié (le texte de la card a changé) ---
            $trail = $occurrence->getSentier();
            if ($trail) {
                $this->cacheFile->deleteTrail($trail->getId());
            }

            $cachedTaxon = $this->cacheFile->getTaxon($referentiel,  $taxon["name_id"]);
            if ($cachedTaxon) {
                $this->cacheFile->deleteTaxon($referentiel,  $taxon["name_id"]);
            }

            $this->em->persist($occurrence);
        }

        $this->em->persist($fiche);
        $this->em->persist($newFiche);
        $this->em->flush();

        // --- Mise à jour du cache fiche ---
        $this->cacheFile->deleteFiche($referentiel, $num_tax);
        $this->cacheFile->saveFiche($referentiel, $num_tax, $newFiche, ['show_fiche']);

        return new JsonResponse($this->serializer->serialize($newFiche, 'json', ['groups' => ['show_fiche']]), Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Response(
     *     response="201",
     *     description="Fiche créée",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Fiche::class, groups={"list_fiche"})
     *     ),
     * )
     * @OA\RequestBody(
     *     description="A JSON object containing page information",
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Fiche::class, groups={"update_fiche"})
     *     )
     * )
     * @OA\Tag(name="Fiches")
     * @OA\Post(
     *     summary="Create a page",
     * )
     */
    #[Route(path: '/fiche/{referentiel}/{num_tax}', name: 'create_fiche', methods: ['POST'])]
    public function createFiche(string $referentiel, int $num_tax, Request $request): Response
    {
        ['user' => $user, 'token'=> $token, 'error' => $error] = $this->annuaire->getUserFromRequest($request);

        if ($error) {
            return new JsonResponse(['error' => $error], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user || !$token) {
            return new JsonResponse(['error' => 'Erreur d\'authentification, veuillez vous reconnecter'], Response::HTTP_UNAUTHORIZED);
        }

        $fiche = $this->sharedService->chercherFiche($referentiel, $num_tax);

        if ($fiche) {
            return new JsonResponse(['error' => 'The page already exist (referentiel: '. $referentiel .', num_tax: '. $num_tax .')'], Response::HTTP_NOT_FOUND);
        }

        $fiche = $this->serializer->deserialize($request->getContent(), Fiche::class, 'json', ['groups' => ['update_fiche']]);

        $fiche->setReferentiel($referentiel);
        $fiche->setNt($num_tax);
        $fiche->setTag($this->sharedService->formaterPageNom($fiche->getReferentiel(), $fiche->getNt()));
        $fiche->setProprietaire($user->getName() ?? 'anonyme');
        $fiche->setUser($user->getId());
        $fiche->setDateModification(new DateTime());
        $fiche->setDerniereVersion(true);

        $this->em->persist($fiche);

        //On cherche les occurrences avec cette fiche et on update le taxon "tabs" et le card tag
        $occurrences = $this->occurrenceRepository->findByTaxon($referentiel, $num_tax);
        foreach ($occurrences as $occurrence) {
            $taxon = $occurrence->getTaxon();
            $taxon['tabs'] = $fiche->getTag();

            $occurrence->setTaxon($taxon);
            $occurrence->setCardTag($fiche->getTag());

            // --- Invalidation du cache taxon et trail lié (le texte de la card a changé) ---
            $trail = $occurrence->getSentier();

            if ($trail) {
                $this->cacheFile->deleteTrail($trail->getId());
            }

            $cachedTaxon = $this->cacheFile->getTaxon($referentiel,  $taxon["name_id"]);
            if ($cachedTaxon) {
                $this->cacheFile->deleteTaxon($referentiel,  $taxon["name_id"]);
            }

            $this->em->persist($occurrence);
        }
        $this->em->flush();

        // --- Mise en cache fiche après création ---
        $this->cacheFile->saveFiche($referentiel, $num_tax, $fiche, ['show_fiche']);

        return new JsonResponse($this->serializer->serialize($fiche, 'json', ['groups' => ['show_fiche']]), Response::HTTP_CREATED, [], true);
    }
}