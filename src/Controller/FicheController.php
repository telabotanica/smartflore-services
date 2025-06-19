<?php

namespace App\Controller;

use App\Entity\Fiche;
use App\Repository\FicheRepository;
use App\Service\FicheService;
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
    private SerializerInterface $serializer;
    private EntityManagerInterface $em;
    private FicheRepository $ficheRepository;
    private FicheService $ficheService;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $em, FicheRepository $ficheRepository, FicheService $ficheService)
    {
        $this->serializer = $serializer;
        $this->em = $em;
        $this->ficheRepository = $ficheRepository;
        $this->ficheService = $ficheService;
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
     *     summary="Get one page",
     * )
     * @Route("/fiche/{referentiel}/{num_tax}", name="single_fiche", methods={"GET"})
     */
    public function getFiche(string $referentiel, int $num_tax): Response
    {
        $nom_page = $this->ficheService->formaterPageNom($referentiel, $num_tax);
        // Fiche SmartFlore eg. SmartFloreBDTFXnt6200
        $fiche = $this->ficheRepository->findOneBy(['tag' => $nom_page, 'derniere_version' => 1]);

        if (!$fiche) {
            $nom_page = $this->ficheService->formaterPageNomGlobal($referentiel, $num_tax);
            // Fiche globale eg. BDTFXnt36750
            $fiche = $this->ficheRepository->findOneBy(['tag' => $nom_page, 'derniere_version' => 1]);
        }

        if (!$fiche) {
            return new JsonResponse(['error' => 'Fiche not found (referentiel: '. $referentiel .', num_tax: '. $num_tax .')'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($fiche, 'json', ['groups' => ['list_fiche']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
