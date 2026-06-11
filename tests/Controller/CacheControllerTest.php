<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class CacheControllerTest extends ControllerTestCase
{
    public function testCacheStatus(): void
    {
        $this->client->request('GET', '/cache/status');

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $content);
    }

    public function testCacheWarmup(): void
    {
        $this->client->request('GET', '/cache/warmup/force');

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('trails', $content);
    }
}
