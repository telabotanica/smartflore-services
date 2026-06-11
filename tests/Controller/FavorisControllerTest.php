<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;

class FavorisControllerTest extends ControllerTestCase
{
    public function testGetFavorisUnauthenticated(): void
    {
        $this->client->request('GET', '/favoris');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetFavoris(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('GET', '/favoris', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertSame('Acer campestre L.', $content[0]['scientific_name']);
    }

    public function testAddFavoris(): void
    {
        $this->mockAnnuaireService();
        $this->mockEfloreService();

        $this->client->request('POST', '/favoris', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'taxon_id' => 99999,
            'referentiel' => 'bdtfx',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Acer campestre', $content['scientific_name']);
        $this->assertSame('bdtfx', $content['referentiel']);
    }

    public function testAddFavorisUnauthenticated(): void
    {
        $this->client->request('POST', '/favoris', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'taxon_id' => 141,
            'referentiel' => 'bdtfx',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAddFavorisMissingFields(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/favoris', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'taxon_id' => 141,
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testAddDuplicateFavoris(): void
    {
        $this->mockAnnuaireService();
        $this->mockHttpClient();

        $this->client->request('POST', '/favoris', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'taxon_id' => 141,
            'referentiel' => 'bdtfx',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('already in your favorite list', $content['error']);
    }

    public function testDeleteFavoris(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('DELETE', '/favoris/' . AppFixtures::FAVORIS_1_ID, [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('Favorite deleted', $this->client->getResponse()->getContent());
    }

    public function testDeleteFavorisNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('DELETE', '/favoris/99999', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteFavorisUnauthenticated(): void
    {
        $this->client->request('DELETE', '/favoris/' . AppFixtures::FAVORIS_1_ID);

        $this->assertResponseStatusCodeSame(401);
    }
}
