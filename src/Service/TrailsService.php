<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
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

            $trailsList->set($response->getContent());
            $this->cache->save($trailsList);
        }

        return $trailsList->get();
    }
}
