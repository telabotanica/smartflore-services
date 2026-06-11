<?php

namespace App\Tests;

use App\Tests\Mock\EfloreApiMock;
use App\Tests\Mock\FakeHttpClient;
use App\Tests\Mock\TrailsApiMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful(string $url): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);

        $this->assertResponseIsSuccessful();
    }

    public function urlProvider(): \Iterator
    {
        yield ['/'];
        yield ['/cache/status'];
//        yield ['/card/text/bdtfx/74934'];
//        yield ['/card/images/bdtfx/74934'];
//        yield ['/taxon/bdtfx/74934'];
    }

    public function testSentierByName(): void
    {
        $client = self::createClient();
        $container = static::getContainer();
        $container->set(HttpClientInterface::class,
            new FakeHttpClient(TrailsApiMock::getResponses() + EfloreApiMock::getResponses()));

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/trail/REVE');

        $this->assertResponseIsSuccessful();
    }

    public function testSentierById(): void
    {
        $client = self::createClient();
        $container = static::getContainer();
        $container->set(HttpClientInterface::class,
            new FakeHttpClient(TrailsApiMock::getResponses() + EfloreApiMock::getResponses()));

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/trail/146');

        $this->assertResponseIsSuccessful();
    }

    public function testTrailsList(): void
    {
        $client = self::createClient();
        $container = static::getContainer();
        $container->set(HttpClientInterface::class,
            new FakeHttpClient(TrailsApiMock::getResponses() + EfloreApiMock::getResponses()));

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/trails');

        $this->assertResponseIsSuccessful();
    }
}
