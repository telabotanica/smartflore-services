<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends ControllerTestCase
{
    public function testAbout(): void
    {
        $this->client->request('GET', '/about');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('À propos');
    }

    public function testCredits(): void
    {
        $this->client->request('GET', '/credits');

        $this->assertResponseIsSuccessful();
    }

    public function testTermsOfUse(): void
    {
        $this->client->request('GET', '/terms_of_use');

        $this->assertResponseIsSuccessful();
    }
}
