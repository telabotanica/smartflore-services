<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\CacheInterface;

class EfloreService
{
    private $client;
    private $cache;
    private $taxonApiBaseUrl;
    private $cardApiUrlTemplate;

    public function __construct(
        string $taxonApiBaseUrl,
        string $cardApiUrlTemplate,
        CacheInterface $taxonCache
    ) {
        $this->client = HttpClient::create();
        $this->cache = $taxonCache;
        $this->taxonApiBaseUrl = $taxonApiBaseUrl;
        $this->cardApiUrlTemplate = $cardApiUrlTemplate;
    }

    public function getTaxonInfo(string $taxonRepo, string $taxonNameId, bool $refresh = false)
    {
        $taxonCache = $this->cache->getItem('taxon.'.$taxonRepo.'.'.$taxonNameId);

        if ($refresh || !$taxonCache->isHit()) {
            // eg. https://api.tela-botanica.org/service:eflore:0.1/taxref/taxons/125328
            $response = $this->client->request('GET', $this->taxonApiBaseUrl.'/'.$taxonRepo.'/taxons/'.$taxonNameId);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $taxon = json_decode($response->getContent());

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
            $cardApiUrl = sprintf($this->cardApiUrlTemplate, strtoupper($taxonRepo).'nt'.$taxonId);
            $response = $this->client->request('GET', $cardApiUrl);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $card = json_decode($response->getContent());

            $cardCache->set($card);
            $this->cache->save($cardCache);
        }

        return $cardCache->get();
    }
}
