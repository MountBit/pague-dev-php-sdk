<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\Auth;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Exceptions\AuthenticationFailed;
use MountBit\PagueDev\Requests\Auth\Token;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class TokenTest extends ApiTestCase
{
    #[Test]
    public function it_generates_an_access_token(): void
    {
        $response = $this->api->send(
            new Token(
                (string) getenv('PAGUEDEV_CLIENT_ID'),
                (string) getenv('PAGUEDEV_CLIENT_SECRET'),
            )
        );

        $this->assertTrue($response->successful());
        $this->assertNotEmpty($response->getAccessToken());
        $this->assertSame('Bearer', $response->getTokenType());
        $this->assertGreaterThan(0, $response->getExpiresIn());
    }

    #[Test]
    public function it_caches_the_access_token_on_the_connector(): void
    {
        $token = $this->api->getAccessToken();

        $this->assertNotEmpty($token);
        $this->assertSame($token, $this->api->getAccessToken());
        $this->assertFalse($this->api->accessTokenHasExpired());
    }

    #[Test]
    public function it_rejects_invalid_credentials(): void
    {
        $api = new Api(
            clientId: 'mp_test_invalid',
            clientSecret: 'invalid-secret',
            baseUrl: getenv('PAGUEDEV_BASE_URL') ?: null,
        );

        $this->expectException(AuthenticationFailed::class);

        $api->getAccessToken();
    }
}
