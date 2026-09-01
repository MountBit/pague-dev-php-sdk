<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use GuzzleHttp\RequestOptions;
use MountBit\PagueDev\Api;
use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Exceptions\InvalidBaseUrl;
use MountBit\PagueDev\Requests\Account\Get as GetAccountRequest;
use MountBit\PagueDev\Requests\Auth\Token as TokenRequest;
use MountBit\PagueDev\Requests\Transactions\GetById as GetTransactionRequest;
use MountBit\PagueDev\Requests\Transactions\Refund as RefundRequest;
use MountBit\PagueDev\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class SecurityTest extends TestCase
{
    private const string SECRET = 'super-secret-client-secret';

    private const string TOKEN = 'super-secret-access-token';

    #[Test]
    public function it_never_follows_redirects(): void
    {
        $api = new Api(accessToken: 'token');

        $config = (new ReflectionMethod($api, 'defaultConfig'))->invoke($api);

        $this->assertFalse($config[RequestOptions::ALLOW_REDIRECTS]);
    }

    #[Test]
    public function it_always_verifies_the_tls_certificate(): void
    {
        $api = new Api(accessToken: 'token');

        $config = (new ReflectionMethod($api, 'defaultConfig'))->invoke($api);

        $this->assertTrue($config[RequestOptions::VERIFY]);
    }

    #[Test]
    public function it_does_not_leak_the_client_secret_when_dumped_or_serialized(): void
    {
        $api = new Api(
            clientId: 'mp_test_id',
            clientSecret: self::SECRET,
            accessToken: self::TOKEN,
        );

        $this->assertStringNotContainsString(self::SECRET, print_r($api, true));
        $this->assertStringNotContainsString(self::TOKEN, print_r($api, true));
        $this->assertStringNotContainsString(self::SECRET, serialize($api));
        $this->assertStringNotContainsString(self::TOKEN, serialize($api));
        $this->assertStringNotContainsString(self::SECRET, json_encode($api->__debugInfo()));
    }

    #[Test]
    public function it_does_not_leak_the_credentials_when_the_auth_request_is_dumped(): void
    {
        $request = new TokenRequest('mp_test_id', self::SECRET);

        $this->assertStringNotContainsString(self::SECRET, print_r($request, true));
        $this->assertStringNotContainsString(self::SECRET, serialize($request));
    }

    #[Test]
    public function it_does_not_leak_the_access_token_through_an_api_exception(): void
    {
        $mockClient = new MockClient([
            GetAccountRequest::class => MockResponse::make([
                'statusCode' => 403,
                'error' => 'Forbidden',
                'message' => 'Permission denied',
            ], 403),
        ]);

        $api = (new Api(
            clientId: 'mp_test_id',
            clientSecret: self::SECRET,
            accessToken: self::TOKEN,
        ))->withMockClient($mockClient);

        $exception = ApiException::fromResponse($api->send(new GetAccountRequest));

        $this->assertStringNotContainsString(self::TOKEN, print_r($exception, true));
        $this->assertStringNotContainsString(self::SECRET, print_r($exception, true));
        $this->assertStringNotContainsString(self::TOKEN, serialize($exception));
        $this->assertStringNotContainsString(self::TOKEN, (string) $exception);
    }

    #[Test]
    public function it_refuses_a_plaintext_base_url(): void
    {
        $this->expectException(InvalidBaseUrl::class);

        new Api(accessToken: 'token', baseUrl: 'http://api-gateway.pague.dev/v2');
    }

    public static function traversalProvider(): array
    {
        return [
            ['../account'],
            ['..%2Faccount'],
            ['../../sub-accounts'],
            ['id/../../withdrawals'],
            ["id\r\nX-Injected: 1"],
        ];
    }

    #[Test]
    #[DataProvider('traversalProvider')]
    public function it_encodes_hostile_identifiers_in_the_path(string $id): void
    {
        $endpoint = (new GetTransactionRequest(id: $id))->resolveEndpoint();

        $this->assertStringStartsWith('/transactions/', $endpoint);
        $this->assertStringNotContainsString('/../', $endpoint);
        $this->assertStringNotContainsString("\r", $endpoint);
        $this->assertStringNotContainsString("\n", $endpoint);
        $this->assertSame(
            '/transactions/'.rawurlencode($id),
            $endpoint
        );
    }

    #[Test]
    public function it_keeps_the_refund_path_segment_intact(): void
    {
        $endpoint = (new RefundRequest(id: '../../withdrawals'))->resolveEndpoint();

        $this->assertStringEndsWith('/refund', $endpoint);
        $this->assertStringNotContainsString('/../', $endpoint);
    }

    #[Test]
    public function it_does_not_parse_the_webhook_body_before_the_signature_is_verified(): void
    {
        $hugePayload = json_encode(['event' => str_repeat('a', 100000)]);

        $this->assertNull(
            Utils::parseWebhook($hugePayload, 'sha256=forged', 'secret')
        );
    }

    #[Test]
    public function it_rejects_a_forged_signature_of_every_shape(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'evt_1',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => ['amount' => 100],
        ]);

        $forged = [
            '',
            'sha256=',
            'sha256=0',
            str_repeat('0', 64),
            'sha256='.str_repeat('0', 64),
            'sha256=sha256='.hash_hmac('sha256', $rawBody, 'secret'),
            hash('sha256', $rawBody),
            hash_hmac('sha256', $rawBody, ''),
            hash_hmac('sha256', $rawBody, hash('sha256', 'secret')),
            hash_hmac('sha256', $rawBody, 'wrong-secret'),
        ];

        foreach ($forged as $signature) {
            $this->assertNull(
                Utils::parseWebhook($rawBody, $signature, 'secret'),
                'Accepted a forged signature: '.$signature
            );
        }
    }

    #[Test]
    public function it_uses_a_constant_time_comparison_for_the_signature(): void
    {
        $source = file_get_contents(__DIR__.'/../src/Utils.php');

        $this->assertStringContainsString('hash_equals(', $source);
        $this->assertStringNotContainsString('$expectedSignature ===', $source);
        $this->assertStringNotContainsString('strcmp($expectedSignature', $source);
    }

    #[Test]
    public function it_does_not_accept_a_replayed_delivery_outside_the_window(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'evt_1',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => [],
        ]);

        $signature = Utils::signWebhook($rawBody, 'secret');

        foreach ([-86400, -3600, -301, 3600] as $offset) {
            $this->assertNull(
                Utils::parseWebhook(
                    $rawBody,
                    $signature,
                    'secret',
                    timestampHeader: (string) ((time() + $offset) * 1000),
                    toleranceInSeconds: 300,
                ),
                'Accepted a delivery '.$offset.'s away from now'
            );
        }
    }

    #[Test]
    public function it_rejects_a_non_numeric_timestamp_header(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'evt_1',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => [],
        ]);

        $signature = Utils::signWebhook($rawBody, 'secret');

        foreach (['', 'now', '-1000', '1e20', '9'.str_repeat('9', 30)] as $header) {
            $this->assertNull(
                Utils::parseWebhook(
                    $rawBody,
                    $signature,
                    'secret',
                    timestampHeader: $header,
                    toleranceInSeconds: 300,
                ),
                'Accepted the timestamp header: '.$header
            );
        }
    }

    #[Test]
    public function it_survives_hostile_webhook_payloads(): void
    {
        $payloads = [
            '',
            'null',
            'false',
            '0',
            '[]',
            '[1,2,3]',
            '"string"',
            '{"event":null}',
            '{"event":{"nested":true},"eventId":"1","timestamp":"t"}',
            '{"event":"payment_completed","eventId":[],"timestamp":"t"}',
            '{"event":"payment_completed","eventId":"1","timestamp":"t","data":"not-an-array"}',
            str_repeat('[', 600).str_repeat(']', 600),
        ];

        foreach ($payloads as $payload) {
            $result = Utils::parseWebhook(
                $payload,
                Utils::signWebhook($payload, 'secret'),
                'secret'
            );

            if ($result !== null) {
                $this->assertIsArray($result->data);
                $this->assertIsString($result->event);
            } else {
                $this->assertNull($result);
            }
        }
    }

    #[Test]
    public function it_does_not_send_credentials_to_a_host_the_caller_did_not_choose(): void
    {
        $api = new Api(accessToken: 'token', baseUrl: 'https://api-gateway.pague.dev/v2');

        $this->assertSame('https://api-gateway.pague.dev/v2', $api->resolveBaseUrl());
        $this->assertSame(
            'https://api-gateway.pague.dev/v2',
            (new Api(accessToken: 'token'))->resolveBaseUrl()
        );
    }
}
