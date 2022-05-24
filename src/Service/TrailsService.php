<?php

namespace App\Service;

use App\Model\Image;
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
    private $efloreService;

    public function __construct(
        string $smartfloreLegacyApiBaseUrl,
        CacheInterface $trailsCache,
        UrlGeneratorInterface $router,
        EfloreService $efloreService
    ) {
        $this->client = HttpClient::create();
        $this->cache = $trailsCache;
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
        $this->router = $router;
        $this->efloreService = $efloreService;
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

            $trails = $serializer->deserialize($response->getContent(), 'App\Model\Trail[]', 'json', [
                'remove_empty_tags' => true
            ]);

            /**
             * @var $trail Trail
             */
            foreach ($trails as &$trail) {
                $trailName = self::extractTrailName($trail);

                // override reference with more details
                $trail = $this->getTrail($trailName, $refresh);

                $trail->computeOccurrencesCount();
                $trail->setDisplayName($trail->getNom());
                $trail->setNom($trailName);
                $trail->setDetails($this->router->generate('show_trail', [
                    'id' => $trail->getNom()
                ], UrlGeneratorInterface::ABSOLUTE_URL));
                $this->collectTrailImages($trail, $refresh);
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

            $this->collectTrailImages($trail, $refresh);

            $trailCache->set($trail);
            $this->cache->save($trailCache);
        }

        return $trailCache->get();
    }

    public function getTrailSpecieImages(string $trailName, bool $refresh = false)
    {
        $trailSpecieImagesCache = $this->cache->getItem('trails.trail.'.$trailName.'.images');

        if ($refresh || !$trailSpecieImagesCache->isHit()) {
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
                throw new \Exception('Response status code is different than expected.');
            }
            $images = json_decode($response->getContent(), true);

            $res = [];
            foreach ($images as $key => $val) {
                $matches = [];
                if (preg_match('@SmartFlore(\w+)nt(\d+)@', $key, $matches)) {
                    $taxoRepo = strtolower($matches[1]);
                    $taxoId = $matches[2];
                    $res[$taxoRepo][$taxoId] = array_map(function ($img) {
                        dump($img);
                        return new Image($img['url'], 'occurrence');
                    }, $val['illustrations']);
                }
            }

            $trailSpecieImagesCache->set($res);
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

    /**
     * Get image collection
     */
    public function collectTrailImages(Trail $trail, bool $refresh = false)
    {
        $occurrencesImages = $this->getTrailSpecieImages($trail->getNom(), $refresh);
        foreach ($trail->getOccurrences() as $occurrence) {
            $taxon = $occurrence->getTaxo();
            $taxonId = $this->efloreService->getTaxonInfo(
                $taxon->getReferentiel(), $taxon->getNumNom(), $refresh)['num_taxonomique'];

            $images = $occurrencesImages[$taxon->getReferentiel()][$taxonId] ?? [];
            $coste = $this->efloreService->getCardCosteImage($taxon->getReferentiel(), $taxonId, $refresh);
            if ($coste) {
                $images[] = $coste;
            }
            $images += $this->efloreService->getCardSpeciesImages(
                $taxon->getReferentiel(), $taxon->getNumNom(), $refresh);

            $images = array_filter($images);
            $occurrence->setImages($images);
            if (!$trail->getImage()) {
                $trail->setImage(reset($images));
            }
        }
    }
}
