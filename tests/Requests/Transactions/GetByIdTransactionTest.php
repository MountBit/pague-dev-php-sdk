<?php

declare(strict_types=1);

namespace Tests\Requests\Transactions;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Transactions\GetById as GetTransactionByIdRequest;
use MountBit\PagueDev\Responses\Transactions\GetById as GetTransactionByIdResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetByIdTransactionTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200()
    {
        $mockResponse = $this->fixture('/transactions/get/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetTransactionByIdRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $transactionId = 'txn_123';

        $request = new GetTransactionByIdRequest(id: $transactionId);

        /** @var GetTransactionByIdResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (GetTransactionByIdRequest $request) => $request->resolveEndpoint() === '/transactions/'.$transactionId
        );

        $this->assertTrue($response instanceof GetTransactionByIdResponse);

        $this->assertSame($mockResponseJson, $response->json());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);
            $result = $response->$getter();
            $this->assertSame($mockResponseJson[$key], $result);
        }
    }
}
