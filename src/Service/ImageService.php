<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Sentier;
use App\Service\EfloreService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageService
{
    private $client;
    private $imageUrl;
    private $imageMiniatureUrl;
    private $ipApiV2Image;
    private $cache;
    private $efloreService;


    public function __construct(
        string $imageUrl,
        string $imageMiniatureUrl,
        string $ipApiV2Image,
        CacheInterface $cache,
        EfloreService $efloreService
    ) {
        /**
         * @var $client HttpClientInterface
         */
        $this->client = HttpClient::create();
        $this->imageUrl = $imageUrl;
        $this->imageMiniatureUrl = $imageMiniatureUrl;
        $this->ipApiV2Image = $ipApiV2Image;
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
        if (count($trail->getOccurrences()) > 0) {
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
//            $occurrence->setImages(array_filter($images));

                if (!$trail->getImage() && $occurrence->getFirstImage()) {
                    $trail->setImage($occurrence->getFirstImage());
                }
            }
        }
    }
*/

    public function findImageForTrail(Sentier $trail)
    {
        $image = null;
        $occurrences = $trail->getOccurrences();
        if (count($occurrences) > 0) {
            foreach ($occurrences as $occurrence) {
                if (count($occurrence->getImages()) != 0){
                    $image = $occurrence->getImages()[0];
                    break;
                }
            }

            if (!$image){
                $occurrence = $occurrences[0];
                $taxon = $occurrence->getTaxon();
                if ($taxon){
                    if (isset($taxon["taxon_repository"]) && isset($taxon["name_id"])){
                        $images = $this->efloreService->getCardSpeciesImages(
                            $taxon["taxon_repository"], $taxon["name_id"]
                        );
                        if (count($images) > 0){
                            $image = $images[0];
                        }
                    }
                }
            }
        }

        if ($image && $image->getCelImageId()) {
            $imageModel = new Image();
            $imageModel->setCelImageId($image->getCelImageId());
            $imageModel->setUrl($image->getUrl());
            $imageModel->setAuthor($image->getAuthor() ?? '');
            $imageModel->setMini($image->getMini() ?? '');
            
            $trail->setImage($imageModel);
        }

    }

    public function findImageFromId(string $image_id)
    {
        $image_api_id = str_pad($image_id, 9, '0', STR_PAD_LEFT);
        $mini = sprintf($this->imageMiniatureUrl, $image_api_id);
        $url = sprintf($this->imageUrl, $image_api_id);
        $author = null;

        $response = $this->client->request('GET', sprintf($this->ipApiV2Image, $image_id), []);
        if (200 == $response->getStatusCode()) {
            $image_data = json_decode($response->getContent());
            $author = $image_data->observation->{'auteur.nom'};
        }

        $image = new \App\Model\Image(
            $image_id,
            $url,
            $author,
            $mini
        );

        return $image;
    }

}