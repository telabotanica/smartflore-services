<?php

namespace App\Service;

use App\Model\Trail;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;

class TrailsService
{
    private $client;
    private $cache;
    private $smartfloreLegacyApiBaseUrl;
    private $router;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        CacheInterface $trailsCache,
        UrlGeneratorInterface $router
    ) {
        $this->client = HttpClient::create();
        $this->cache = $trailsCache;
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
        $this->router = $router;
    }

    /**
     * @param bool $refresh
     * @return Trail[]
     */
    public function getTrails(bool $refresh = false)
    {
        $trailsCache = $this->cache->getItem('trails.list');

        if ($refresh || !$trailsCache->isHit()) {
            $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.'sentiers/', [
                'timeout' => 180,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }

            $extractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
            $normalizer = [
                new ArrayDenormalizer(),
                new ObjectNormalizer(null, null, null, $extractor),
            ];
            $serializer = new Serializer($normalizer, [new JsonEncoder()]);

            $trails = $serializer->deserialize($response->getContent(), 'App\Model\Trail[]', 'json');

            /**
             * @var $trail Trail
             */
            foreach ($trails as $trail) {
                $trail->setDisplayName($trail->getNom());
                $trail->setNom(self::extractTrailName($trail));
                $trail->setDetails($this->router->generate('trail_details', [
                    'id' => $trail->getNom()
                ], UrlGeneratorInterface::ABSOLUTE_URL));
            }

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
                if ('Ce sentier n\'existe pas' === $response->getContent(false)) {
                    throw new TrailNotFoundException('This trail does not exist');
                }
                throw new \Exception('Response status code is different than expected.');
            }

            $extractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
            $normalizer = [
                new ArrayDenormalizer(),
                new ObjectNormalizer(null, null, null, $extractor),
            ];
            $serializer = new Serializer($normalizer, [new JsonEncoder()]);

            $trail = $serializer->deserialize($response->getContent(), Trail::class, 'json');

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
            $images = json_decode($response->getContent(), true);

            $trailSpecieImagesCache->set($images);
            $this->cache->save($trailSpecieImagesCache);
        }

        return $trailSpecieImagesCache->get();
    }

    /**
     * @param string $id It's a string because trail ID is deserialized as a string (maybe should fix)
     */
    public function getTrailname(string $id): string
    {
        $trails = $this->getTrails();
        foreach ($trails as $trail) {
            if ($trail->getId() === $id) {
                return $trail->getNom();
            }
        }
        return '';
    }

    public static function extractTrailName(Trail $trail): string
    {
        if ($trail->getDetails()) {
            $parts = explode('/', $trail->getDetails());
            return urldecode(end($parts));
        }

        return '';
    }
}
