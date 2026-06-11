<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;

class PingControllerTest extends ControllerTestCase
{
    public function testPing(): void
    {
        $this->client->request('POST', '/ping', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'isLogged' => false,
            'isLocated' => true,
            'isOnline' => true,
            'trail' => AppFixtures::TRAIL_VALIDATED_ID,
            'from_website' => true,
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonStringEqualsJsonString(
            '"Ping saved in Database"',
            $this->client->getResponse()->getContent()
        );
    }

    public function testPingDuplicate(): void
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $this->client->request('POST', '/ping', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'isLogged' => false,
            'isLocated' => true,
            'isOnline' => true,
            'trail' => AppFixtures::TRAIL_VALIDATED_ID,
            'from_website' => true,
            'date' => $now,
        ]));

        $this->assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/ping', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'isLogged' => false,
            'isLocated' => true,
            'isOnline' => true,
            'trail' => AppFixtures::TRAIL_VALIDATED_ID,
            'from_website' => true,
            'date' => $now,
        ]));

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonStringEqualsJsonString(
            '"Ping already registered today"',
            $this->client->getResponse()->getContent()
        );
    }

    public function testPingInvalidPayload(): void
    {
        $this->client->request('POST', '/ping', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'isLogged' => 'not-a-boolean',
        ]));

        $this->assertResponseStatusCodeSame(500);
    }

    public function testPingDetails(): void
    {
        $this->client->request('GET', '/ping/' . AppFixtures::TRAIL_VALIDATED_ID);

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testPingDetailsNotFound(): void
    {
        $this->client->request('GET', '/ping/99999');

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $this->assertCount(0, $content);
    }
}
