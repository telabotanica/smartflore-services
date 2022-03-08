<?php

namespace App\Service;

use App\Model\Trails;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
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
        $trailsList = $this->cache->getItem('trails.list');
        if ($refresh || !$trailsList->isHit()) {
            $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl, [
                'timeout' => 120,
                'headers' => [
                    'Accept: application/json',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Response status code is different than expected.');
            }

            $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
            $normalizer = [
                new ArrayDenormalizer(),
                new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, $extractor),
            ];
            $serializer = new Serializer($normalizer, [new JsonEncoder()]);

            $trails = $serializer->deserialize($response->getContent(), 'App\Model\Trails[]', 'json');

            die(var_dump($trails));

            $trailsList->set($trails);
            $this->cache->save($trailsList);
        }

        return $trailsList->get();
    }
}
