<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Transactions;

use MountBit\PagueDev\Requests\Transactions\Refund as RefundRequest;
use MountBit\PagueDev\Responses\Transactions\Refund as RefundResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class RefundTransactionRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201(): void
    {
        $mockResponse = $this->fixture('/transactions/refund/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            RefundRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $request = new RefundRequest(
            id: $mockResponseJson['originalTransactionId'],
            reason: 'Cliente solicitou cancelamento',
        );

        /** @var RefundResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (RefundRequest $request) => $request->body()->all() === [
                'reason' => 'Cliente solicitou cancelamento',
            ]
        );

        $this->assertTrue($response instanceof RefundResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $this->assertEquals($mockResponseJson[$key], $response->$getter());
        }
    }

    #[Test]
    public function it_sends_an_empty_body_when_no_reason_is_given(): void
    {
        $request = new RefundRequest(id: '3c90c3cc-0d44-4b50-8888-8dd25736052a');

        $this->assertSame([], $request->defaultBody());
    }

    #[Test]
    public function it_resolves_the_refund_endpoint(): void
    {
        $request = new RefundRequest(id: '3c90c3cc-0d44-4b50-8888-8dd25736052a');

        $this->assertSame(
            '/transactions/3c90c3cc-0d44-4b50-8888-8dd25736052a/refund',
            $request->resolveEndpoint()
        );
    }
}
