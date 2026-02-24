<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Pix\Static;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Pix\Static\Create as CreateRequest;
use MountBit\PagueDev\Responses\Pix\Static\Create as CreateResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreatePixRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201(): void
    {
        $mockResponse = $this->fixture('/pix/static/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $payload = [
            'amount' => 25.0,
            'description' => 'Doação para ONG',
            'projectId' => '3c90c3cc-0d44-4b50-8888-8dd25736052a',
            'externalReference' => 'pedido-12345',
            'metadata' => [
                'orderId' => '12345',
                'source' => 'website',
            ],
        ];

        $request = new CreateRequest(
            amount: $payload['amount'],
            description: $payload['description'],
            projectId: $payload['projectId'],
            externalReference: $payload['externalReference'],
            metadata: $payload['metadata'],
        );

        /** @var CreateResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (CreateRequest $request) => $request->body()->all() === $payload,
        );

        $this->assertTrue($response instanceof CreateResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $result = $response->$getter();

            $this->assertEquals($mockResponseJson[$key], $result);
        }

        $qrCode = $response->getQrCode();

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/svg+xml;base64', $qrCode);
    }
}
