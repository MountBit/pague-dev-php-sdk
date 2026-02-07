<?php

declare(strict_types=1);

namespace Tests\Requests\Pix;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Dtos\Pix\Customer;
use MountBit\PagueDev\Requests\Pix\Create as CreateRequest;
use MountBit\PagueDev\Responses\Pix\Create as CreateResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreatePixRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200()
    {
        $mockResponse = $this->fixture('/pix/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $payload = [
            'amount' => 200.50,
            'description' => 'Test PIX Payment',
            'projectId' => 'proj_pix_123',
            'customer' => [
                'name' => 'John Doe',
                'document' => '111111',
                'email' => 'john@example.com',
                'phone' => '12345',
            ],
            'expiresIn' => 3600,
            'externalReference' => 'ref_123',
            'metadata' => ['orderId' => 'order_001'],
        ];

        $request = new CreateRequest(
            amount: $payload['amount'],
            description: $payload['description'],
            projectId: $payload['projectId'],
            customer: new Customer(
                name: $payload['customer']['name'],
                document: $payload['customer']['document'],
                email: $payload['customer']['email'],
                phone: $payload['customer']['phone'],
            ),
            expiresIn: $payload['expiresIn'],
            externalReference: $payload['externalReference'],
            metadata: $payload['metadata'],
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

        $qrCode = $response->getQrCode();

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/svg+xml;base64', $qrCode);
    }
}
