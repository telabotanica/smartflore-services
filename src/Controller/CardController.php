<?php

namespace App\Controller;

use App\Service\EfloreService;
use App\Service\TrailsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CardController extends AbstractController
{
    /**
     * @Route("/fiche/text/{taxonRepo}/{taxonNameId}", name="card_text")
     */
    public function cardText(EfloreService $eflore, string $taxonRepo, string $taxonNameId)
    {
        $taxonId = $eflore->getTaxonInfo($taxonRepo, $taxonNameId)->num_taxonomique;
        return $this->json($eflore->getCardText($taxonRepo, $taxonId));
    }

    /**
     * @Route("/fiche/images/{taxonRepo}/{taxonNameId}", name="card_images")
     */
    public function cardImages(
        EfloreService $eflore,
        string $taxonRepo,
        string $taxonNameId
    ) {
        $images = $eflore->getCardSpeciesImages($taxonRepo, $taxonNameId)->resultats;
dump($images);
        // array with image id / urls / author ?

        return $this->json($images);
    }

    /**
     * @Route("/fiche/images/{taxonRepo}/{taxonNameId}/{trailName}", name="card_specie_images")
     */
    public function cardSpecieImages(
        EfloreService $eflore,
        TrailsService $trailsService,
        string $taxonRepo,
        string $taxonNameId,
        string $trailName
    ) {
        $taxonId = $eflore->getTaxonInfo($taxonRepo, $taxonNameId)->num_taxonomique;
        $trailSpecieImages = $trailsService->getTrailSpecieImages($trailName, $taxonRepo, $taxonId);

        // array with image id / urls / author ?

        return $this->json($trailSpecieImages);
    }

    /**
     * @Route("/fiche/images/coste/{taxonRepo}/{taxonNameId}", name="card_coste_image")
     */
    public function cardCosteImage(
        EfloreService $eflore,
        string $taxonRepo,
        string $taxonNameId
    ) {
        $taxonId = $eflore->getTaxonInfo($taxonRepo, $taxonNameId)->num_taxonomique;
        $coste = $eflore->getCardCosteImage($taxonRepo, $taxonId)->resultats;

        // array with image id / urls / author ?

        return $this->json($coste);
    }
}
