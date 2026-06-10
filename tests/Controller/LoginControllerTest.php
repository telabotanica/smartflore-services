<?php

namespace App\Tests\Controller;

use App\Service\AnnuaireService;
use Symfony\Component\HttpFoundation\Response;

class LoginControllerTest extends ControllerTestCase
{
    public function testRegister(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirect', $content);
        $this->assertArrayHasKey('text', $content);
    }

    public function testLogout(): void
    {
        $this->client->request('GET', '/logout');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithEmptyFields(): void
    {
        $this->client->request('POST', '/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'login' => '',
            'password' => '',
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginRefreshWithEmptyToken(): void
    {
        $this->client->request('POST', '/login/refresh');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testAdminCheckUnauthenticated(): void
    {
        $this->client->request('GET', '/admincheck');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminCheckForbidden(): void
    {
        $this->mockAnnuaireService();
        $this->client->request('GET', '/admincheck', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('false', $this->client->getResponse()->getContent());
    }

    public function testAdminCheckAdmin(): void
    {
        $this->mockAdminAnnuaireService();
        $this->client->request('GET', '/admincheck', ['token' => 'fake-token-123']);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('true', $this->client->getResponse()->getContent());
    }
}
