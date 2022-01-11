<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class SentiersService
{
    private $client;
    private $smartfloreLegacyApiBaseUrl;

    public function __construct(string $smartfloreLegacyApiBaseUrl)
    {
        $this->client = HttpClient::create();
        $this->smartfloreLegacyApiBaseUrl = $smartfloreLegacyApiBaseUrl;
    }

    public function getSentiers()
    {
        $response = $this->client->request('GET', $this->smartfloreLegacyApiBaseUrl, [
            'timeout' => 120,
            'headers' => [
                'Accept: application/json',
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Response status code is different than expected.');
        }

        return $response->getContent();
    }
}
