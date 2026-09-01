<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use GuzzleHttp\Utils;
use MountBit\PagueDev\Api;
use MountBit\PagueDev\Exceptions\AuthenticationFailed;
use MountBit\PagueDev\Exceptions\InvalidBaseUrl;
use MountBit\PagueDev\Exceptions\MissingCredentials;
use MountBit\PagueDev\Requests\Account\Get as GetAccountRequest;
use MountBit\PagueDev\Requests\Auth\Token as TokenRequest;
use MountBit\PagueDev\Requests\SubAccounts\GetList as GetSubAccountsRequest;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionProperty;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class ApiTest extends TestCase
{
    #[Test]
    public function it_uses_the_default_base_url_when_none_is_provided(): void
    {
        $api = new Api(accessToken: 'test-token');

        $this->assertSame(
            'https://api-gateway.pague.dev/v2',
            $api->resolveBaseUrl()
        );
    }

    #[Test]
    public function it_uses_a_custom_base_url_when_provided(): void
    {
        $api = new Api(
            accessToken: 'test-token',
            baseUrl: 'https://example.com'
        );

        $this->assertSame('https://example.com', $api->resolveBaseUrl());
    }

    #[Test]
    public function it_requires_credentials_or_an_access_token(): void
    {
        $this->expectException(MissingCredentials::class);

        new Api;
    }

    #[Test]
    public function it_requires_the_client_secret_when_the_client_id_is_given(): void
    {
        $this->expectException(MissingCredentials::class);

        new Api(clientId: 'mp_test_id');
    }

    #[Test]
    public function it_uses_bearer_authentication_with_the_access_token(): void
    {
        $api = new Api(accessToken: 'static-token');

        $authenticator = $this->invokeMethod($api, 'defaultAuth');

        $this->assertInstanceOf(TokenAuthenticator::class, $authenticator);
        $this->assertSame('static-token', $authenticator->token);
    }

    #[Test]
    public function it_sets_default_headers(): void
    {
        $api = new Api(accessToken: 'test-token');

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $this->assertSame('application/json', $headers['Accept']);
        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertSame('pague.dev - PHP SDK', $headers['User-Agent']);
        $this->assertArrayNotHasKey(Api::SUB_ACCOUNT_HEADER, $headers);
    }

    #[Test]
    public function it_builds_the_default_user_agent_when_none_is_provided(): void
    {
        $api = new Api(accessToken: 'test-token', userAgent: null);

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $this->assertSame(
            sprintf('pague.dev - PHP SDK (%s)', Utils::defaultUserAgent()),
            $headers['User-Agent']
        );
    }

    #[Test]
    public function it_merges_extra_headers_without_overriding_the_defaults(): void
    {
        $api = new Api(
            accessToken: 'test-token',
            extraHeaders: [
                'X-Custom' => 'value',
                'Accept' => 'text/plain',
            ]
        );

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $this->assertSame('value', $headers['X-Custom']);
        $this->assertSame('application/json', $headers['Accept']);
    }

    #[Test]
    public function it_sends_the_sub_account_header_when_a_sub_account_is_provided(): void
    {
        $api = new Api(accessToken: 'test-token', subAccount: 'loja-centro');

        $headers = $this->invokeMethod($api, 'defaultHeaders');

        $this->assertSame('loja-centro', $headers[Api::SUB_ACCOUNT_HEADER]);
    }

    #[Test]
    public function it_returns_a_new_connector_for_another_sub_account(): void
    {
        $api = new Api(accessToken: 'test-token', subAccount: 'loja-centro');

        $other = $api->forSubAccount('loja-norte');

        $this->assertNotSame($api, $other);
        $this->assertSame('loja-centro', $api->subAccount);
        $this->assertSame('loja-norte', $other->subAccount);
        $this->assertSame('test-token', $other->getAccessToken());
    }

    #[Test]
    public function it_requests_an_access_token_with_the_client_credentials(): void
    {
        $mockClient = new MockClient([
            TokenRequest::class => MockResponse::make($this->fixture('/auth/200.json'), 200),
        ]);

        $api = (new Api(
            clientId: 'mp_test_id',
            clientSecret: 'secret',
        ))->withMockClient($mockClient);

        $token = $api->getAccessToken();

        $this->assertSame(
            'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.test.signature',
            $token
        );

        $mockClient->assertSent(
            fn (TokenRequest $request) => $request->body()->all() === [
                'client_id' => 'mp_test_id',
                'client_secret' => 'secret',
            ]
        );
    }

    #[Test]
    public function it_caches_the_access_token_between_requests(): void
    {
        $mockClient = new MockClient([
            TokenRequest::class => MockResponse::make($this->fixture('/auth/200.json'), 200),
            GetAccountRequest::class => MockResponse::make($this->fixture('/account/200.json'), 200),
        ]);

        $api = (new Api(
            clientId: 'mp_test_id',
            clientSecret: 'secret',
        ))->withMockClient($mockClient);

        $api->send(new GetAccountRequest);
        $api->send(new GetAccountRequest);

        $tokenRequests = array_filter(
            $mockClient->getRecordedResponses(),
            fn ($response) => $response->getPendingRequest()->getRequest() instanceof TokenRequest
        );

        $this->assertCount(1, $tokenRequests);
    }

    #[Test]
    public function it_throws_when_the_credentials_are_rejected(): void
    {
        $mockClient = new MockClient([
            TokenRequest::class => MockResponse::make([
                'error' => 'unauthorized',
                'message' => 'Invalid credentials',
            ], 401),
        ]);

        $api = (new Api(
            clientId: 'mp_test_id',
            clientSecret: 'wrong-secret',
        ))->withMockClient($mockClient);

        $this->expectException(AuthenticationFailed::class);
        $this->expectExceptionMessage('Invalid credentials');

        $api->getAccessToken();
    }

    #[Test]
    public function it_throws_when_refreshing_without_credentials(): void
    {
        $api = new Api(accessToken: 'static-token');

        $this->expectException(MissingCredentials::class);

        $api->getAccessToken(forceRefresh: true);
    }

    #[Test]
    public function it_marks_the_access_token_as_expired_after_its_lifetime(): void
    {
        $api = new Api(accessToken: 'static-token', accessTokenExpiresIn: 10);

        $this->assertTrue($api->accessTokenHasExpired());
    }

    #[Test]
    public function it_never_expires_an_access_token_without_a_lifetime(): void
    {
        $api = new Api(accessToken: 'static-token');

        $this->assertFalse($api->accessTokenHasExpired());
    }

    #[Test]
    public function it_does_not_send_the_sub_account_header_on_sub_account_endpoints(): void
    {
        $mockClient = new MockClient([
            GetSubAccountsRequest::class => MockResponse::make(
                $this->fixture('/sub-accounts/list/200.json'),
                200
            ),
        ]);

        $connector = $this->connector($mockClient, subAccount: 'loja-centro');

        $connector->send(new GetSubAccountsRequest);

        $mockClient->assertSent(
            fn ($request, $response) => $response
                ->getPendingRequest()
                ->headers()
                ->get(Api::SUB_ACCOUNT_HEADER) === null
        );
    }

    #[Test]
    public function it_rejects_a_base_url_that_is_not_https(): void
    {
        $this->expectException(InvalidBaseUrl::class);

        new Api(accessToken: 'test-token', baseUrl: 'http://api-gateway.pague.dev/v2');
    }

    #[Test]
    public function it_redacts_the_credentials_when_the_connector_is_dumped(): void
    {
        $api = new Api(clientId: 'mp_test_id', clientSecret: 'super-secret');

        $debug = $api->__debugInfo();

        $this->assertSame('mp_test_id', $debug['clientId']);
        $this->assertSame('[redacted]', $debug['clientSecret']);

        $dump = print_r($api, true);

        $this->assertStringNotContainsString('super-secret', $dump);
    }

    #[Test]
    public function it_does_not_expose_the_client_secret_as_a_property(): void
    {
        $api = new Api(clientId: 'mp_test_id', clientSecret: 'super-secret');

        $this->assertFalse(
            (new ReflectionProperty($api, 'clientSecret'))->isPublic()
        );
    }

    private function invokeMethod(object $object, string $method): mixed
    {
        return (new ReflectionMethod($object, $method))->invoke($object);
    }
}
