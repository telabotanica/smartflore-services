<?php

namespace App\Controller;

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
     *     description="Taxonomic info",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Model(type=Taxon::class, groups={"show_trail"})
     *     )
     * )
     * @OA\Parameter(
     *     name="taxonRepo",
     *     in="path",
     *     description="The taxon repository code (""référentiel"")",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="taxonId",
     *     in="path",
     *     description="The taxonomic id (""numéro taxonomique"")",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Taxon")
     * @Route("/taxon/{taxonRepo}/{taxonId}", name="show_taxon", methods={"GET"})
     */
    public function taxonInfo(
        SerializerInterface $serializer,
        EfloreService $eflore,
        string $taxonRepo,
        int $taxonId
    ) {
        $json = $serializer->serialize(
            $eflore->getTaxonInfo($taxonRepo, $taxonId),
            'json', ['groups' => 'show_taxon']);

        return new JsonResponse($json, 200, [], true);
    }
}
