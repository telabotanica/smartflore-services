<?php

namespace App\Tests\Controller;

use App\Model\Trail;
use App\Model\User;
use App\DataFixtures\AppFixtures;
use App\Service\AnnuaireService;
use Symfony\Component\HttpFoundation\Response;

class MeControllerTest extends ControllerTestCase
{
    public function testMeWithoutAuth(): void
    {
        $this->client->request('GET', '/me');

        $this->assertResponseStatusCodeSame(401);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testMeWithAuth(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('GET', '/me', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $content);
        $this->assertSame(AppFixtures::TEST_USER_ID, $content['id']);
        $this->assertArrayHasKey('trails', $content);
        $this->assertIsArray($content['trails']);
    }

    public function testMeWithAuthReturnsUserTrails(): void
    {
        $this->mockAnnuaireService();

        $this->client->request('GET', '/me', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('trails', $content);
        $this->assertIsArray($content['trails']);
    }
}
