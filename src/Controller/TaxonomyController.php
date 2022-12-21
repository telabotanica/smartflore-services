<?php

namespace App\Controller;

use App\Model\Referentiel;
use App\Model\Taxon;
use App\Service\EfloreService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class TaxonomyController extends AbstractController
{
    /**
     * @OA\Response (
     *     response="200",
     *     description="Taxonomic info and more",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Taxon::class, groups={"show_taxon", "full_images"})
     *     )
     * )
     * @OA\Parameter(
     *     name="taxonRepository",
     *     in="path",
     *     description="The taxon repository code (""référentiel"")",
     *     example="bdtfx",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="taxonNameId",
     *     in="path",
     *     description="The taxonomic name id (""num nom"")",
     *     example="141",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Taxon")
     * @Route("/taxon/{taxonRepository}/{taxonNameId}", name="show_taxon", methods={"GET"})
     */
    public function taxonInfo(
        SerializerInterface $serializer,
        EfloreService $eflore,
        string $taxonRepository,
        int $taxonNameId
    ) {
        $json = $serializer->serialize(
            $eflore->getTaxon($taxonRepository, $taxonNameId),
            'json', ['groups' => ['show_taxon', 'full_images']]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @OA\Response (
     *     response="200",
     *     description="get the taxon repository codes (referentiels)",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Referentiel::class, groups={"show_taxon", "list_referentiel"}))
     *     )
     * )
     * @OA\Tag(name="Taxon")
     * @Route("/taxon/referentiels", name="list_referentiel", methods={"GET"})
     */
    public function referentielInfo(SerializerInterface $serializer,EfloreService $eflore){
        $referentiels= $eflore->getTaxonRepositories();

        $json = $serializer->serialize($referentiels, 'json', ['groups' => 'list_referentiel']);

        return new JsonResponse($json, 200, [], true);
    }
}
