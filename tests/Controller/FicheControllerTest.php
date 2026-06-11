<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;

class FicheControllerTest extends ControllerTestCase
{
    public function testGetFiche(): void
    {
        $this->client->request('GET', '/fiche/bdtfx/' . AppFixtures::FICHE_BDTFX_NT);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('SmartFloreBDTFXnt' . AppFixtures::FICHE_BDTFX_NT, $content['tag']);
        $this->assertSame('Une belle plante méditerranéenne', $content['description']);
    }

    public function testGetFicheNotFound(): void
    {
        $this->client->request('GET', '/fiche/bdtfx/99999999');

        $this->assertResponseStatusCodeSame(404);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testUpdateFiche(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('PUT', '/fiche/bdtfx/' . AppFixtures::FICHE_BDTFX_NT, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'description' => 'Description mise à jour',
            'usages' => 'Usages mis à jour',
        ]));

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Description mise à jour', $content['description']);
        $this->assertSame('Usages mis à jour', $content['usages']);
    }

    public function testUpdateFicheUnauthenticated(): void
    {
        $this->client->request('PUT', '/fiche/bdtfx/' . AppFixtures::FICHE_BDTFX_NT, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'description' => 'Test',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateFicheNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('PUT', '/fiche/bdtfx/99999999', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode(['description' => 'Test']));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateFicheEmptyDescription(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('PUT', '/fiche/bdtfx/' . AppFixtures::FICHE_BDTFX_NT, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode(['description' => '   ']));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateFiche(): void
    {
        $this->mockAnnuaireService();
        $this->mockHttpClient();

        $this->client->request('POST', '/fiche/bdtfx/11111', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'description' => 'Nouvelle fiche de test',
            'usages' => 'Usage test',
            'ecologie' => 'Écologie test',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Nouvelle fiche de test', $content['description']);
        $this->assertSame('bdtfx', $content['referentiel']);
    }

    public function testCreateExistingFiche(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/fiche/bdtfx/' . AppFixtures::FICHE_BDTFX_NT, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'description' => 'Test',
        ]));

        $this->assertResponseStatusCodeSame(404);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('already exist', $content['error']);
    }

    public function testCreateFicheUnauthenticated(): void
    {
        $this->client->request('POST', '/fiche/bdtfx/11111', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'description' => 'Test',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }
}
