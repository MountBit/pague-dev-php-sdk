<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Transactions;

use MountBit\PagueDev\Requests\Transactions\GetById as GetByIdRequest;
use MountBit\PagueDev\Responses\Transactions\GetById as GetByIdResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetByIdTransactionRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/transactions/get/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetByIdRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $request = new GetByIdRequest(id: $mockResponseJson['id']);

        /** @var GetByIdResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $this->assertTrue($response instanceof GetByIdResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $this->assertEquals($mockResponseJson[$key], $response->$getter());
        }
    }

    #[Test]
    public function it_accepts_an_external_reference_as_the_identifier(): void
    {
        $request = new GetByIdRequest(id: 'pedido-12345');

        $this->assertSame('/transactions/pedido-12345', $request->resolveEndpoint());
    }

    #[Test]
    public function it_encodes_the_transaction_id_in_the_endpoint(): void
    {
        $request = new GetByIdRequest(id: '../account');

        $this->assertSame('/transactions/..%2Faccount', $request->resolveEndpoint());
    }
}
