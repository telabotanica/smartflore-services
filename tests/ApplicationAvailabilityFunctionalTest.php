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
        yield ['/cache/warmup'];
        yield ['/trail'];
        yield ['/trail/Sur les traces des mineurs de Gréasque'];
        yield ['/card/text/bdtfx/74934'];
        yield ['/card/images/bdtfx/74934'];
        yield ['/card/images/bdtfx/74934/Sur les traces des mineurs de Gréasque'];
        yield ['/card/images/coste/bdtfx/74934'];
    }
}
