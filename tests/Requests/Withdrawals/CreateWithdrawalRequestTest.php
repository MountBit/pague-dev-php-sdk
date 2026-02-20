<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Withdrawals;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Withdrawals\Create as CreateRequest;
use MountBit\PagueDev\Responses\Withdrawals\Create as CreateResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreateWithdrawalRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201()
    {
        $mockResponse = $this->fixture('/withdrawals/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $payload = [
            'amount' => 150.75,
            'pixKey' => '12345678901',
            'pixKeyType' => 'cpf',
            'holderName' => 'João da Silva',
            'holderDocument' => '12345678901',
            'holderDocumentType' => 'cpf',
        ];

        $request = new CreateRequest(
            amount: $payload['amount'],
            pixKey: $payload['pixKey'],
            pixKeyType: $payload['pixKeyType'],
            holderName: $payload['holderName'],
            holderDocument: $payload['holderDocument'],
            holderDocumentType: $payload['holderDocumentType'],
        );

        /** @var CreateResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (CreateRequest $request) => $request->body()->all() === $payload
        );

        $this->assertTrue($response instanceof CreateResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);
            $result = $response->$getter();
            $this->assertEquals($mockResponseJson[$key], $result);
        }
    }
}
