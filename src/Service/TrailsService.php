<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;

class TrailsService
{
    private $client;
    private $cache;
    private $smartfloreLegacyApiBaseUrl;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        CacheInterface $trailsCache
    ) {
        $this->client = HttpClient::create();
        $this->cache = $trailsCache;
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
    }

    public function getTrails(bool $refresh = false)
    {
        $trailsCache = $this->cache->getItem('trails.list');

        if ($refresh || !$trailsCache->isHit()) {
            $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentiers/', [
                'timeout' => 120,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }

//            $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
//            $normalizer = [
//                new ArrayDenormalizer(),
//                new PropertyNormalizer(),
//                new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, $extractor),
//            ];
//            $serializer = new Serializer($normalizer, [new JsonEncoder()]);
//
//            $data = $response->getContent();
//
//            $trails = $serializer->deserialize($data, 'App\Model\Trail[]', 'json');

            $trails = json_decode($response->getContent());

            $trailsCache->set($trails);
            $this->cache->save($trailsCache);
        }

        return $trailsCache->get();
    }

    public function getTrail(string $trailName, bool $refresh = false)
    {
        $trailCache = $this->cache->getItem('trails.trail.'.$trailName);

        if ($refresh || !$trailCache->isHit()) {
            $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentiers/'.urlencode($trailName), [
                'timeout' => 120,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $trail = json_decode($response->getContent());

            $trailCache->set($trail);
            $this->cache->save($trailCache);
        }

        return $trailCache->get();
    }

    public function getTrailSpecieImages(string $trailName, string $taxonRepo, string $taxonId, bool $refresh = false)
    {
        $trailSpecieImagesCache = $this->cache->getItem('trails.trail.'.$trailName.'.images.'.strtoupper($taxonRepo).'nt'.$taxonId);

        if ($refresh || !$trailSpecieImagesCache->isHit()) {
            // https://www.tela-botanica.org/smart-form/services/Sentiers.php/sentier-illustration-fiche/?sentierTitre=Sentier%20botanique%20de%20la%20r%C3%A9serve%20naturelle%20Tr%C3%A9sor&ficheTag=SmartFloreTAXREFnt731626
            $url = $this->smartfloreLegacyApiBaseUrl
                .'sentier-illustration-fiche/?sentierTitre='.urlencode($trailName)
                .'&ficheTag=SmartFlore'.strtoupper($taxonRepo).'nt'.$taxonId;
            $response = $this->client->request('GET', $url, [
                'timeout' => 120,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }
            $images = json_decode($response->getContent());

            $trailSpecieImagesCache->set($images);
            $this->cache->save($trailSpecieImagesCache);
        }

        return $trailSpecieImagesCache->get();
    }
}
