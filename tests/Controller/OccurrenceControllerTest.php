<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;

class OccurrenceControllerTest extends ControllerTestCase
{
    public function testAddOccurrence(): void
    {
        $this->mockAnnuaireService();
        $this->mockHttpClient();

        $this->client->request('POST', '/occurrence/' . AppFixtures::TRAIL_DRAFT_ID, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'position' => ['lat' => 45.001, 'lng' => 5.001],
            'anecdotes' => 'Nouvelle occurrence test',
            'scientific_name' => 'Acer campestre',
            'taxon_repository' => 'bdtfx',
            'name_id' => 141,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('occurrences', $content);
    }

    public function testAddOccurrenceUnauthenticated(): void
    {
        $this->client->request('POST', '/occurrence/' . AppFixtures::TRAIL_DRAFT_ID, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'position' => ['lat' => 45.001, 'lng' => 5.001],
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAddOccurrenceTrailNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/occurrence/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'position' => ['lat' => 45.001, 'lng' => 5.001],
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAddOccurrenceToPublishedTrail(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/occurrence/' . AppFixtures::TRAIL_VALIDATED_ID, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'position' => ['lat' => 43.61, 'lng' => 3.88],
        ]));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateOccurrence(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('PUT', '/occurrence/' . AppFixtures::OCC_PENDING_1_ID, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'anecdotes' => 'Anecdote mise à jour',
            'position' => ['lat' => 43.6105, 'lng' => 3.8765],
        ]));

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Anecdote mise à jour', $content['anecdotes']);
    }

    public function testUpdateOccurrenceNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('PUT', '/occurrence/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode(['anecdotes' => 'Test']));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateOccurrenceUnauthenticated(): void
    {
        $this->client->request('PUT', '/occurrence/' . AppFixtures::OCC_PENDING_1_ID, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['anecdotes' => 'Test']));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteOccurrence(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('DELETE', '/occurrence/' . AppFixtures::OCC_PENDING_1_ID, [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Occurrence deleted', $this->client->getResponse()->getContent());
    }

    public function testDeleteOccurrenceNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('DELETE', '/occurrence/99999', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteOccurrenceUnauthenticated(): void
    {
        $this->client->request('DELETE', '/occurrence/' . AppFixtures::OCC_PENDING_1_ID);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteImage(): void
    {
        $this->mockAdminAnnuaireService();

        $this->client->request('DELETE', '/occurrence/image/' . AppFixtures::IMAGE_FULL_1_ID, [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteImageNotFound(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('DELETE', '/occurrence/image/99999', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteImageUnauthenticated(): void
    {
        $this->client->request('DELETE', '/occurrence/image/' . AppFixtures::IMAGE_FULL_1_ID);

        $this->assertResponseStatusCodeSame(401);
    }
}
