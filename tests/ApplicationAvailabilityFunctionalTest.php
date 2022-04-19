<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public function urlProvider()
    {
        yield ['/cache/status'];
//        yield ['/cache/warmup'];
//        yield ['/sentiers'];
        yield ['/sentiers/Sur les traces des mineurs de Gréasque'];
        yield ['/fiche/text/bdtfx/74934']; // OK
        yield ['/fiche/images/bdtfx/74934']; // OK
        yield ['/fiche/images/bdtfx/74934/Sur les traces des mineurs de Gréasque']; // OK
        yield ['/fiche/images/coste/bdtfx/74934'];
    }
}
