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
            $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl, [
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
            $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl.urlencode($trailName), [
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
}
