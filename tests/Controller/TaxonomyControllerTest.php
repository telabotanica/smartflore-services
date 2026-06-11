<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class TaxonomyControllerTest extends ControllerTestCase
{
    public function testReferentiels(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/taxon/referentiels');

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testTaxonInfo(): void
    {
        $this->mockEfloreService();
        $this->client->request('GET', '/taxon/bdtfx/141');

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testTaxonInfoFromNt(): void
    {
        $this->mockEfloreService();
        $this->client->request('GET', '/taxon/bdtfx/nt/8522');

        $this->assertResponseIsSuccessful();
    }

    public function testTaxonNotFound(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/taxon/bdtfx/99999999');

        $this->assertResponseStatusCodeSame(400);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testSearchTaxons(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/taxons', ['referentiel' => 'bdtfx']);

        $this->assertResponseIsSuccessful();
    }

    public function testSearchTaxonsMissingReferentiel(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/taxons');

        $this->assertResponseStatusCodeSame(400);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertStringContainsString('referentiel', $content['error']);
    }

    public function testSearchTaxonsRetourMin(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/taxons', [
            'referentiel' => 'bdtfx',
            'retour' => 'min',
            'recherche' => 'acer',
        ]);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testTaxonsSearchEndpointNoRetourMin(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/taxons/search', ['referentiel' => 'bdtfx']);

        $this->assertResponseStatusCodeSame(400);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testTaxonsSearchEndpointRetourMin(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/taxons/search', [
            'referentiel' => 'bdtfx',
            'retour' => 'min',
            'recherche' => 'acer',
        ]);

        $this->assertResponseIsSuccessful();
    }
}
