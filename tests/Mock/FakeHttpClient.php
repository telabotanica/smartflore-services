<?php

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class FakeHttpClient implements HttpClientInterface
{
    /**
     * @var array
     */
    private $responses;

    /**
     * We create an array of response with the $url as "key" and $response as "value"
     * ex: $responses = [
     *      '/external/user_exists' => new MockResponse($content)
     * ];
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $response = null;
        foreach (array_keys($this->responses) as $key) {
            if (str_starts_with($url, $key)) {
                $response = $this->responses[$key];
                break;
            }
        }

        if (null === $response) {
            throw new \LogicException(sprintf('There is no response for url: %s', $url));
        }

        return (new MockHttpClient($response))->request($method, $url);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        throw new \LogicException(sprintf('%s() is not implemented', __METHOD__));
    }

    public function withOptions(): self
    {
        return $this;
    }
}
