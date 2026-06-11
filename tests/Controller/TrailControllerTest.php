<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;

class TrailControllerTest extends ControllerTestCase
{
    public function testTrailsList(): void
    {
        $this->client->request('GET', '/trails');

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $names = array_map(fn($t) => $t['name'], $content);
        $this->assertContains('Forêt Enchantée', $names);
        $this->assertContains('Montagne Bleue', $names);
        $this->assertNotContains('Vallée Perdue', $names);
        $this->assertNotContains('Jardin Secret', $names);
    }

    public function testTrailsListWithBboxFilter(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/trails', ['bbox' => '10.0,10.0,-10.0,-10.0']);

        $this->assertResponseIsSuccessful();
    }

    public function testTrailById(): void
    {
        $this->client->request('GET', '/trail/' . AppFixtures::TRAIL_VALIDATED_ID);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Forêt Enchantée', $content['display_name']);
        $this->assertArrayHasKey('occurrences', $content);
        $this->assertCount(2, $content['occurrences']);
    }

    public function testTrailByIdNotFound(): void
    {
        $this->client->request('GET', '/trail/99999');

        $this->assertResponseStatusCodeSame(404);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testTrailByIdDeleted(): void
    {
        $this->client->request('GET', '/trail/' . AppFixtures::TRAIL_DELETED_ID);

        $this->assertResponseStatusCodeSame(404);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testBatchTrail(): void
    {
        $this->client->request('GET', '/batch/trail/' . AppFixtures::TRAIL_VALIDATED_ID);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('occurrences', $content);
    }

    public function testBatchTrailNotFound(): void
    {
        $this->client->request('GET', '/batch/trail/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTrailTaxons(): void
    {
        $this->mockHttpClient();
        $this->client->request('GET', '/trail/' . AppFixtures::TRAIL_FULL_ID . '/taxons');

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testTrailTaxonsNotFound(): void
    {
        $this->client->request('GET', '/trail/99999/taxons');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCheckTrailNotFound(): void
    {
        $this->client->request('GET', '/trail/99999/check');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCheckTrailAlreadyPublished(): void
    {
        $this->client->request('GET', '/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/check');

        $this->assertResponseStatusCodeSame(403);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('already published', $content['error']);
    }

    public function testCreateTrail(): void
    {
        $this->mockAnnuaireService();
        $this->mockEfloreService();

        $this->client->request('POST', '/trail', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'name' => 'Nouveau Sentier Test',
            'prm' => 1,
            'best_season' => [true, true, false, false],
            'position' => ['start' => ['lat' => 48.0, 'lng' => 2.0], 'end' => ['lat' => 48.0, 'lng' => 2.0]],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Nouveau Sentier Test', $content['display_name']);
    }

    public function testCreateTrailUnauthenticated(): void
    {
        $annuaire = $this->createMock(\App\Service\AnnuaireService::class);
        $annuaire->method('getRequestToken')->willReturn(null);
        $annuaire->method('getUserFromRequest')->willReturn(['user' => null, 'token' => null, 'error' => 'No token found, veuillez vous reconnecter']);
        self::getContainer()->set(\App\Service\AnnuaireService::class, $annuaire);

        $this->client->request('POST', '/trail', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Nouveau Sentier',
            'prm' => 1,
            'best_season' => [true, false, false, false],
            'position' => ['start' => ['lat' => 48.0, 'lng' => 2.0], 'end' => ['lat' => 48.0, 'lng' => 2.0]],
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateTrailInvalidJson(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/trail', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateTrail(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('PUT', '/trail/' . AppFixtures::TRAIL_DRAFT_ID, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'name' => 'Chemin des Dames (Mis à jour)',
            'prm' => 0,
        ]));

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Chemin des Dames (Mis à jour)', $content['display_name']);
    }

    public function testUpdateTrailNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('PUT', '/trail/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode(['name' => 'Test']));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateTrailForbiddenWhenPublished(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('PUT', '/trail/' . AppFixtures::TRAIL_VALIDATED_ID, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode(['name' => 'Test']));

        $this->assertResponseStatusCodeSame(403);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('already published', $content['error']);
    }

    public function testUpdateTrailUnauthenticated(): void
    {
        $this->client->request('PUT', '/trail/' . AppFixtures::TRAIL_DRAFT_ID, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test']));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteTrail(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('DELETE', '/trail/' . AppFixtures::TRAIL_DRAFT_ID, [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(202);
        $this->assertStringContainsString('deleted', $this->client->getResponse()->getContent());
    }

    public function testDeleteTrailNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('DELETE', '/trail/99999', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteTrailUnauthenticated(): void
    {
        $this->client->request('DELETE', '/trail/' . AppFixtures::TRAIL_DRAFT_ID);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testReviewTrail(): void
    {
        $this->mockAnnuaireService();
        $this->mockCreateTrailService();
        $this->mockHttpClient();

        $this->client->request('POST', '/trail/' . AppFixtures::TRAIL_DRAFT_ID . '/review', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('En attente', $content['status']);
    }

    public function testReviewTrailNotFound(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/trail/99999/review', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testReviewTrailAlreadyPublished(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/review', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateTrailImage(): void
    {
        $this->mockAnnuaireService();
        $this->mockHttpClient();

        $this->client->request('PUT', '/trail/' . AppFixtures::TRAIL_DRAFT_ID . '/update-image', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'fake-token-123',
        ], json_encode([
            'image' => ['id' => 9999, 'cel_image_id' => 1001, 'url' => 'https://test/img.jpg'],
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateTrailImageUnauthenticated(): void
    {
        $this->client->request('PUT', '/trail/' . AppFixtures::TRAIL_DRAFT_ID . '/update-image', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'image' => ['id' => 9999],
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteTrailImage(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('DELETE', '/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/delete-image', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteTrailImageUnauthenticated(): void
    {
        $this->client->request('DELETE', '/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/delete-image');

        $this->assertResponseStatusCodeSame(401);
    }
}
