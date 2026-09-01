<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Auth;

use MountBit\PagueDev\Requests\Auth\Token as TokenRequest;
use MountBit\PagueDev\Responses\Auth\Token as TokenResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class TokenRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/auth/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            TokenRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $request = new TokenRequest('mp_test_id', 'secret');

        /** @var TokenResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (TokenRequest $request) => $request->body()->all() === [
                'client_id' => 'mp_test_id',
                'client_secret' => 'secret',
            ]
        );

        $this->assertTrue($response instanceof TokenResponse);

        $this->assertSame($mockResponseJson, $response->toArray());
        $this->assertSame($mockResponseJson['access_token'], $response->getAccessToken());
        $this->assertSame('Bearer', $response->getTokenType());
        $this->assertSame(300, $response->getExpiresIn());
    }

    #[Test]
    public function it_resolves_the_auth_endpoint(): void
    {
        $this->assertSame('/auth', (new TokenRequest('id', 'secret'))->resolveEndpoint());
    }
}
