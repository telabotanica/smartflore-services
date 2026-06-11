<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;

class AdminControllerTest extends ControllerTestCase
{
    public function testAdminTrailsList(): void
    {
        $this->mockAdminAnnuaireService();

        $this->client->request('GET', '/admin/trails', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $names = array_map(fn($t) => $t['name'], $content);
        $this->assertContains('Forêt Enchantée', $names);
        $this->assertContains('Vallée Perdue', $names);
        $this->assertContains('Jardin Secret', $names);
    }

    public function testAdminTrailsListForbidden(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('GET', '/admin/trails', ['token' => 'fake-token-123']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminTrailsListUnauthenticated(): void
    {
        $this->client->request('GET', '/admin/trails');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPublishTrail(): void
    {
        $this->mockAdminAnnuaireService();
        $this->mockCreateTrailService();
        $this->mockHttpClient();

        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_PENDING_ID . '/publish', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Validé', $content['status']);
        $this->assertArrayHasKey('date_publication', $content);
    }

    public function testPublishTrailNotFound(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/99999/publish', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPublishTrailForbidden(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_PENDING_ID . '/publish', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPublishTrailAlreadyPublished(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/publish', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnpublishTrail(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/unpublish', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testUnpublishTrailNotPublished(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_PENDING_ID . '/unpublish', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRejectTrail(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_PENDING_ID . '/reject', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNull($content['status']);
    }

    public function testRejectTrailNotPending(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/reject', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testReactivateTrail(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_DELETED_ID . '/reactivate', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNull($content['date_suppression'] ?? null);
    }

    public function testReactivateTrailNotDeleted(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('POST', '/admin/trail/' . AppFixtures::TRAIL_VALIDATED_ID . '/reactivate', [], [], [
            'HTTP_Authorization' => 'fake-token-123',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
