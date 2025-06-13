<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Sentier;
use App\Service\EfloreService;
use Symfony\Contracts\Cache\CacheInterface;

class ImageService
{
    private $cache;
    private $efloreService;

    public function __construct(
        CacheInterface $cache,
        EfloreService $efloreService
    ) {
        $this->cache = $cache;
        $this->efloreService = $efloreService;
    }
/*
    public function buildTrailImagesCache(Sentier $trail): void
    {
        $occurrencesImages = $this->getTrailSpecieImages($trail->getNom(), true);
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxon();
            $images = $occurrence->getImages();
//            dd($occurrence);
            $images = $occurrencesImages[$taxon->getReferentiel()][$taxon->getTaxonomicId()] ?? [];
            $images += $this->efloreService->getCardSpeciesImages(
                $taxon->getReferentiel(), $taxon->getNumNom(), true);

            $coste = $this->efloreService->getCardCosteImage(
                $taxon->getReferentiel(), $taxon->getTaxonomicId(), true);
            if ($coste) {
                $images[] = $coste;
            }

            $occurrence->setImages(array_filter($images));

            if (!$trail->getImage() && $occurrence->getFirstImage()) {
                $trail->setImage($occurrence->getFirstImage());
            }
        }
    }
*/
    public function getTrailSpecieImages(string $trailName, bool $refresh = false)
    {
        $trailSpecieImagesCache = $this->cache->getItem('trails.trail.'.$trailName.'.images');

/*
        if ($refresh || !$trailSpecieImagesCache->isHit()) {
            //TODO: get from BDD
            // https://www.tela-botanica.org/smart-form/services/Sentiers.php/sentier-illustration-fiche/?sentierTitre=Sentier%20botanique%20de%20la%20r%C3%A9serve%20naturelle%20Tr%C3%A9sor
            $url = $this->smartfloreLegacyApiBaseUrl
                .'sentier-illustration-fiche/?sentierTitre='.urlencode($trailName);
            $response = $this->client->request('GET', $url, [
                'timeout' => 120,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Erreur lors de la récupération des images espèces.');
            }
            $images = json_decode($response->getContent(), true);

            $res = [];
            foreach ($images as $key => $val) {
                $matches = [];
                if (preg_match('@SmartFlore(\w+)nt(\d+)@', $key, $matches)) {
                    $taxonRepo = strtolower($matches[1]);
                    $taxonId = $matches[2];
                    $res[$taxonRepo][$taxonId] = array_map(static function ($img) {
                        // @todo: find a service to get author info by image id
                        return new Image((int)$img['id'], $img['url'], 'Inconnu');
                    }, $val['illustrations']);
                }
            }

            $trailSpecieImagesCache->set($res);
            $this->cache->save($trailSpecieImagesCache);
        }
*/
        return $trailSpecieImagesCache->get();
    }

    public function findOneImagePlease(Sentier $trail): void
    {
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxon();
            if (count($occurrence->getImages()) == 0){
                $images = $this->efloreService->getCardSpeciesImages(
                    $taxon["taxon_repository"], $taxon["name_id"]
                );
                foreach ($images as $image) {
                    $occurrence->addImage($image);
                }
            }
        }
    }

    /**
     * Get image collection
     * TODO: voir si encore utile
     */
    /*
    public function collectTrailImages(Sentier $trail): void
    {
        $occurrencesImages = $this->getTrailSpecieImages($trail->getNom());
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxon();
//            $taxon = $occurrence->getTaxo();

            $images = $occurrencesImages[$taxon->getReferentiel()][$taxon->getTaxonomicId()] ?? [];
            $images += $this->efloreService->getCardSpeciesImages(
                $taxon->getReferentiel(), $taxon->getNumNom());

            $coste = $this->efloreService->getCardCosteImage(
                $taxon->getReferentiel(), $taxon->getTaxonomicId());
            if ($coste) {
                $images[] = $coste;
            }

            //TODO: a updater ?
            $occurrence->setImages(array_filter($images));

            if (!$trail->getImage() && $occurrence->getFirstImage()) {
                //TODO: a updater ?
                $trail->setImage($occurrence->getFirstImage());
            }
        }
    }
    */
}