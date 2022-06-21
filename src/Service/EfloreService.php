<?php

namespace App\Service;

use App\Model\Image;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\Cache\CacheInterface;

class EfloreService
{
    private $client;
    private $cache;
    private $taxonApiBaseUrl;
    private $cardApiBaseUrl;
    private $imagesApiUrlTemplate;
    private $imageCosteApiUrlTemplate;

    public function __construct(
        string $taxonApiBaseUrl,
        string $cardApiBaseUrl,
        string $imagesApiUrlTemplate,
        string $imageCosteApiUrlTemplate,
        bool $useNativeHttpClient,
        CacheInterface $trailsCache
    ) {
        if ($useNativeHttpClient) {
            $this->client = new NativeHttpClient();
        } else {
            $this->client = HttpClient::create();
        }

        $this->cache = $trailsCache;
        $this->taxonApiBaseUrl = $taxonApiBaseUrl;
        $this->cardApiBaseUrl = $cardApiBaseUrl;
        $this->imagesApiUrlTemplate = $imagesApiUrlTemplate;
        $this->imageCosteApiUrlTemplate = $imageCosteApiUrlTemplate;
    }

    public function getTaxonInfo(string $taxonRepo, int $taxonNameId, bool $refresh = false)
    {
        $taxonCache = $this->cache->getItem('taxon.'.$taxonRepo.'.'.$taxonNameId);

        if ($refresh || !$taxonCache->isHit()) {
            // eg. https://api.tela-botanica.org/service:eflore:0.1/taxref/taxons/125328
            $response = $this->client->request('GET',
                $this->taxonApiBaseUrl.$taxonRepo.'/taxons/'.$taxonNameId,
                ['timeout' => 120]
            );

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $taxon = json_decode($response->getContent(), true);

            $taxonCache->set($taxon);
            $this->cache->save($taxonCache);
        }

        return $taxonCache->get();
    }

    public function getCardText(string $taxonRepo, string $taxonId, bool $refresh = false)
    {
        $cardCache = $this->cache->getItem('taxon.card.SmartFlore'.strtoupper($taxonRepo).'nt'.$taxonId);

        if ($refresh || !$cardCache->isHit()) {
            // eg. https://www.tela-botanica.org/wikini/eFloreRedaction/api/rest/0.5/pages/SmartFloreBDTFXnt6293?txt.format=text/html&txt.section.titre=Description%2CUsages%2C%C3%89cologie+%26+habitat%2CSources
            $cardApiUrl = $this->cardApiBaseUrl.'SmartFlore'.strtoupper($taxonRepo).'nt'.$taxonId
                .'?txt.format=text/html&txt.section.titre='.urlencode('Description,Usages,Écologie & habitat,Sources');
            $response = $this->client->request('GET', $cardApiUrl, ['timeout' => 120]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $card = json_decode($response->getContent(), true);

            $cardCache->set($card);
            $this->cache->save($cardCache);
        }

        return $cardCache->get();
    }

    public function getCardSpeciesImages(string $taxonRepo, string $taxonNameId, bool $refresh = false)
    {
        $cardImagesCache = $this->cache->getItem('taxon.card.images.'.$taxonNameId);

        if ($refresh || !$cardImagesCache->isHit()) {
            // eg. https://api.tela-botanica.org/service:del:0.1/images?navigation.depart=0&navigation.limite=4&masque.standard=1&masque.referentiel=bdtfx&masque.nn=74934&tri=votes&ordre=desc&protocole=3&format=CRS
            $imagesApiUrl = sprintf($this->imagesApiUrlTemplate, $taxonRepo, $taxonNameId);
            $response = $this->client->request('GET', $imagesApiUrl, ['timeout' => 120]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $images = json_decode($response->getContent(), true)['resultats'];

            $res = [];
            foreach ($images as $image) {
                $res[] = new Image($image['id_image'], $image['binaire.href']);
            }

            $cardImagesCache->set($res);
            $this->cache->save($cardImagesCache);
        }

        return $cardImagesCache->get();
    }

    public function getCardCosteImage(string $taxonRepo, string $taxonId, bool $refresh = false)
    {
        $cardImageCosteCache = $this->cache->getItem('taxon.card.images.coste.'.$taxonId);

        if ($refresh || !$cardImageCosteCache->isHit()) {
            // eg. https://api.tela-botanica.org/service:eflore:0.1/coste/images?masque.nt=29926&referentiel=bdtfx
            $imageCosteApiUrl = sprintf($this->imageCosteApiUrlTemplate, $taxonId, $taxonRepo);
            $response = $this->client->request('GET', $imageCosteApiUrl);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $image = json_decode($response->getContent(), true)['resultats'] ?? [];
            $image = reset($image) ?: [];
            if ($image) {
                $image = new Image(0, $image['binaire.href']);
            }

            $cardImageCosteCache->set($image ?? []);
            $this->cache->save($cardImageCosteCache);
        }

        return $cardImageCosteCache->get();
    }
}
