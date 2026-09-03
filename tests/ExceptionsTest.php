<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Exceptions\AuthenticationFailed;
use MountBit\PagueDev\Exceptions\BadRequest;
use MountBit\PagueDev\Exceptions\Conflict;
use MountBit\PagueDev\Exceptions\Forbidden;
use MountBit\PagueDev\Exceptions\NotFound;
use MountBit\PagueDev\Exceptions\ServerError;
use MountBit\PagueDev\Exceptions\TooManyRequests;
use MountBit\PagueDev\Exceptions\Unauthorized;
use MountBit\PagueDev\Exceptions\UnprocessableEntity;
use MountBit\PagueDev\Requests\Account\Get as GetAccountRequest;
use MountBit\PagueDev\Requests\Auth\Token as TokenRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;

class ExceptionsTest extends TestCase
{
    public static function statusProvider(): array
    {
        return [
            [400, BadRequest::class],
            [401, Unauthorized::class],
            [403, Forbidden::class],
            [404, NotFound::class],
            [409, Conflict::class],
            [422, UnprocessableEntity::class],
            [429, TooManyRequests::class],
            [500, ServerError::class],
            [503, ServerError::class],
        ];
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function it_maps_each_status_to_its_exception(int $status, string $expected): void
    {
        $response = $this->respondWith([
            'statusCode' => $status,
            'error' => 'Error',
            'message' => 'Something went wrong',
            'timestamp' => '2026-09-01T19:26:01.916Z',
        ], $status);

        $exception = ApiException::fromResponse($response);

        $this->assertInstanceOf($expected, $exception);
        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertSame($status, $exception->statusCode);
        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    #[Test]
    public function it_exposes_the_business_error_code_from_the_details(): void
    {
        $response = $this->respondWith([
            'statusCode' => 409,
            'error' => 'Conflict',
            'message' => "reference 'catequize' já usado nesta conta",
            'details' => ['code' => 'SUB_ACCOUNT_REFERENCE_TAKEN'],
            'timestamp' => '2026-09-01T19:26:01.916Z',
            'traceId' => 'be0a39e2dfb44b924445f04b0fce1928',
        ], 409);

        $exception = ApiException::fromResponse($response);

        $this->assertInstanceOf(Conflict::class, $exception);
        $this->assertSame('SUB_ACCOUNT_REFERENCE_TAKEN', $exception->getErrorCode());
        $this->assertTrue(
            $exception->hasErrorCode(ApiException::SUB_ACCOUNT_REFERENCE_TAKEN)
        );
        $this->assertSame('be0a39e2dfb44b924445f04b0fce1928', $exception->getTraceId());
        $this->assertSame('Conflict', $exception->error);
        $this->assertSame(
            ['code' => 'SUB_ACCOUNT_REFERENCE_TAKEN'],
            $exception->getDetails()
        );
    }

    #[Test]
    public function it_falls_back_to_the_error_field_when_there_is_no_business_code(): void
    {
        $response = $this->respondWith([
            'statusCode' => 404,
            'error' => 'NotFound',
            'message' => 'Project not found',
            'details' => ['resource' => 'Project'],
        ], 404);

        $exception = ApiException::fromResponse($response);

        $this->assertSame('NotFound', $exception->getErrorCode());
    }

    #[Test]
    public function it_keeps_each_validation_message_exactly_as_the_api_sent(): void
    {
        $response = $this->respondWith([
            'statusCode' => 400,
            'error' => 'BadRequest',
            'message' => ['amount must be a number', 'description should not be empty'],
        ], 400);

        $exception = ApiException::fromResponse($response);

        $this->assertSame('amount must be a number', $exception->getMessage());

        $this->assertSame(
            ['amount must be a number', 'description should not be empty'],
            $exception->getMessages()
        );
    }

    #[Test]
    public function it_uses_the_raw_api_body_when_the_response_is_not_json(): void
    {
        $mockClient = new MockClient([
            GetAccountRequest::class => MockResponse::make('<html>gateway error</html>', 502),
        ]);

        $response = $this->connector($mockClient)->send(new GetAccountRequest);

        $exception = ApiException::fromResponse($response);

        $this->assertInstanceOf(ServerError::class, $exception);
        $this->assertSame('<html>gateway error</html>', $exception->getMessage());
        $this->assertSame([], $exception->getDetails());
        $this->assertNull($exception->getTraceId());
    }

    #[Test]
    public function it_never_replaces_the_api_message_with_its_own(): void
    {
        $response = $this->respondWith([
            'statusCode' => 400,
            'error' => 'BadRequest',
            'message' => "reference 'catequize' já usado nesta conta",
        ], 400);

        $this->assertSame(
            "reference 'catequize' já usado nesta conta",
            ApiException::fromResponse($response)->getMessage()
        );
    }

    #[Test]
    public function it_leaves_the_message_empty_when_the_api_sends_no_body(): void
    {
        $mockClient = new MockClient([
            GetAccountRequest::class => MockResponse::make('', 503),
        ]);

        $response = $this->connector($mockClient)->send(new GetAccountRequest);

        $exception = ApiException::fromResponse($response);

        $this->assertSame('', $exception->getMessage());
        $this->assertSame([], $exception->getMessages());
        $this->assertSame(503, $exception->statusCode);
        $this->assertInstanceOf(ServerError::class, $exception);
    }

    #[Test]
    public function it_exposes_the_whole_error_envelope_documented_by_the_api(): void
    {
        $envelope = [
            'statusCode' => 400,
            'error' => 'BadRequest',
            'message' => 'amount must not be less than 1',
            'details' => ['code' => 'VALIDATION_ERROR', 'field' => 'amount'],
            'timestamp' => '2026-09-01T19:26:01.916Z',
            'traceId' => 'be0a39e2dfb44b924445f04b0fce1928',
        ];

        $exception = ApiException::fromResponse($this->respondWith($envelope, 400));

        $this->assertSame($envelope, $exception->toArray());

        $this->assertSame($envelope['statusCode'], $exception->statusCode);
        $this->assertSame($envelope['error'], $exception->error);
        $this->assertSame($envelope['message'], $exception->getMessage());
        $this->assertSame($envelope['details'], $exception->getDetails());
        $this->assertSame($envelope['timestamp'], $exception->timestamp);
        $this->assertSame($envelope['traceId'], $exception->getTraceId());
    }

    #[Test]
    public function it_handles_the_reduced_envelope_returned_by_the_auth_endpoint(): void
    {
        $mockClient = new MockClient([
            TokenRequest::class => MockResponse::make([
                'error' => 'Unauthorized',
                'message' => 'Invalid credentials',
            ], 401),
        ]);

        $api = (new Api(
            clientId: 'mp_test_id',
            clientSecret: 'wrong',
        ))->withMockClient($mockClient);

        try {
            $api->getAccessToken();
            $this->fail('An AuthenticationFailed exception was expected');
        } catch (AuthenticationFailed $exception) {
            $this->assertSame('Invalid credentials', $exception->getMessage());
            $this->assertSame('Unauthorized', $exception->error);
            $this->assertSame(401, $exception->statusCode);
            $this->assertSame([], $exception->getDetails());
            $this->assertNull($exception->timestamp);
            $this->assertNull($exception->getTraceId());
            $this->assertSame(
                ['error' => 'Unauthorized', 'message' => 'Invalid credentials'],
                $exception->toArray()
            );
        }
    }

    #[Test]
    public function it_does_not_throw_by_default(): void
    {
        $mockClient = new MockClient([
            GetAccountRequest::class => MockResponse::make(['statusCode' => 404], 404),
        ]);

        $response = $this->connector($mockClient)->send(new GetAccountRequest);

        $this->assertTrue($response->failed());
        $this->assertSame(404, $response->status());
    }

    #[Test]
    public function it_throws_the_mapped_exception_when_asked_to(): void
    {
        $mockClient = new MockClient([
            GetAccountRequest::class => MockResponse::make([
                'statusCode' => 403,
                'error' => 'Forbidden',
                'message' => 'Sub account is suspended',
                'details' => ['code' => 'SUB_ACCOUNT_SUSPENDED'],
            ], 403),
        ]);

        $api = (new Api(
            accessToken: 'test-access-token',
            throwOnErrors: true,
        ))->withMockClient($mockClient);

        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('Sub account is suspended');

        $api->send(new GetAccountRequest);
    }

    #[Test]
    public function it_throws_the_mapped_exception_on_demand(): void
    {
        $mockClient = new MockClient([
            GetAccountRequest::class => MockResponse::make([
                'statusCode' => 401,
                'error' => 'Unauthorized',
                'message' => 'Token expired',
            ], 401),
        ]);

        $response = $this->connector($mockClient)->send(new GetAccountRequest);

        $this->expectException(Unauthorized::class);

        $response->throw();
    }

    private function respondWith(array $body, int $status): Response
    {
        $mockClient = new MockClient([
            GetAccountRequest::class => MockResponse::make($body, $status),
        ]);

        return $this->connector($mockClient)->send(new GetAccountRequest);
    }
}
