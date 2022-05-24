<?php

namespace App\Controller;

use App\Service\EfloreService;
use App\Service\TrailsService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CardController extends AbstractController
{
    /**
     * @OA\Response(
     *     response="200",
     *     description="Specie card text",
     *     @OA\JsonContent(
     *         type="object"
     *     )
     * )
     * @OA\Parameter(
     *     name="taxonRepo",
     *     in="path",
     *     description="The taxon repository code (""référentiel"")",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="taxonNameId",
     *     in="path",
     *     description="The taxon name code (""numéro nomenclatural"")",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Cards")
     * @Route("/card/text/{taxonRepo}/{taxonNameId}", name="card_text", methods={"GET"})
     */
    public function cardText(
        EfloreService $eflore,
        string $taxonRepo,
        int $taxonNameId
    ) {
        $taxonId = $eflore->getTaxonInfo($taxonRepo, $taxonNameId)['num_taxonomique'];

        return $this->json($eflore->getCardText($taxonRepo, $taxonId));
    }

    /**
     * @OA\Response(
     *     response="200",
     *     description="Specie card images",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(type="object")
     *     )
     * )
     * @OA\Parameter(
     *     name="taxonRepo",
     *     in="path",
     *     description="The taxon repository code (""référentiel"")",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="taxonNameId",
     *     in="path",
     *     description="The taxon name code (""numéro nomenclatural"")",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Cards")
     * @Route("/card/images/{taxonRepo}/{taxonNameId}", name="card_images", methods={"GET"})
     */
    public function cardImages(
        EfloreService $eflore,
        string $taxonRepo,
        int $taxonNameId
    ) {
        $images = $eflore->getCardSpeciesImages($taxonRepo, $taxonNameId)['resultats'];

        // array with image id / urls / author ?

        return $this->json($images);
    }
}
