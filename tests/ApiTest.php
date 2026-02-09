<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use GuzzleHttp\Utils;
use MountBit\PagueDev\Api;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Saloon\Http\Auth\HeaderAuthenticator;

class ApiTest extends TestCase
{
    #[Test]
    public function it_uses_the_default_base_url_when_none_is_provided(): void
    {
        $api = new Api(apiKey: 'test-key');

        $this->assertSame(
            'https://api.pague.dev/v1',
            $api->resolveBaseUrl()
        );
    }

    #[Test]
    public function it_uses_a_custom_base_url_when_provided(): void
    {
        $api = new Api(
            apiKey: 'test-key',
            baseUrl: 'https://example.com'
        );

        $this->assertSame(
            'https://example.com',
            $api->resolveBaseUrl()
        );
    }

    #[Test]
    public function it_uses_header_authentication_with_api_key(): void
    {
        $api = new Api(apiKey: 'secret-key');

        $authenticator = $this->invokeMethod($api, 'defaultAuth');

        $this->assertInstanceOf(HeaderAuthenticator::class, $authenticator);
        $this->assertSame('secret-key', $authenticator->accessToken);
        $this->assertSame('X-API-Key', $authenticator->headerName);
    }

    #[Test]
    public function it_sets_default_headers(): void
    {
        $api = new Api(apiKey: 'test-key');

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $this->assertSame('application/json', $headers['Accept']);
        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertArrayHasKey('User-Agent', $headers);
    }

    #[Test]
    public function it_uses_custom_user_agent_when_provided(): void
    {
        $api = new Api(
            apiKey: 'test-key',
            userAgent: 'My-Custom-UA'
        );

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $this->assertSame('My-Custom-UA', $headers['User-Agent']);
    }

    #[Test]
    public function it_falls_back_to_default_user_agent_when_empty(): void
    {
        $api = new Api(
            apiKey: 'test-key',
            userAgent: ''
        );

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $expected = sprintf(
            '%s (%s)',
            'pague.dev - PHP SDK',
            Utils::defaultUserAgent()
        );

        $this->assertSame($expected, $headers['User-Agent']);
    }

    #[Test]
    public function it_merges_extra_headers_with_defaults(): void
    {
        $api = new Api(
            apiKey: 'test-key',
            extraHeaders: [
                'X-Custom' => 'value',
            ]
        );

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $this->assertSame('value', $headers['X-Custom']);
        $this->assertSame('application/json', $headers['Accept']);
    }

    #[Test]
    public function it_sets_default_timeouts(): void
    {
        $api = new Api(apiKey: 'test-key');

        $this->assertSame(5.0, $api->getConnectTimeout());
        $this->assertSame(10.0, $api->getRequestTimeout());
    }

    private function invokeMethod(object $object, string $method): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke($object);
    }
}
