<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Pix;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Dtos\Pix\Customer;
use MountBit\PagueDev\Dtos\Pix\SplitAllocation;
use MountBit\PagueDev\Requests\Pix\Create as CreateRequest;
use MountBit\PagueDev\Responses\Pix\Create as CreateResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreatePixRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201(): void
    {
        $mockResponse = $this->fixture('/pix/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $connector = $this->connector($mockClient);

        $payload = [
            'amount' => 200.5,
            'description' => 'Test PIX Payment',
            'customer' => [
                'name' => 'John Doe',
                'document' => '12345678909',
                'email' => 'john@example.com',
                'phone' => '+5511999998888',
            ],
            'projectId' => '3c90c3cc-0d44-4b50-8888-8dd25736052a',
            'pspCredentialId' => '6e307aa4-4772-4230-a648-d88cee308f54',
            'expiresIn' => 3600,
            'externalReference' => 'ref_123',
            'metadata' => ['orderId' => 'order_001'],
            'split' => [
                ['subAccount' => 'loja-centro', 'amount' => 10.5],
            ],
        ];

        $request = new CreateRequest(
            amount: $payload['amount'],
            description: $payload['description'],
            customer: new Customer(
                name: $payload['customer']['name'],
                document: $payload['customer']['document'],
                email: $payload['customer']['email'],
                phone: $payload['customer']['phone'],
            ),
            projectId: $payload['projectId'],
            pspCredentialId: $payload['pspCredentialId'],
            expiresIn: $payload['expiresIn'],
            externalReference: $payload['externalReference'],
            metadata: $payload['metadata'],
            split: [new SplitAllocation('loja-centro', 10.5)],
            idempotencyKey: 'pedido-12345',
        );

        /** @var CreateResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (CreateRequest $request) => $request->body()->all() === $payload,
        );

        $mockClient->assertSent(
            fn ($request, $response) => $response
                ->getPendingRequest()
                ->headers()
                ->get(Api::IDEMPOTENCY_KEY_HEADER) === 'pedido-12345'
        );

        $this->assertTrue($response instanceof CreateResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $this->assertEquals($mockResponseJson[$key], $response->$getter());
        }

        $qrCode = $response->getQrCode();

        $this->assertIsString($qrCode);
        $this->assertStringStartsWith('data:image/svg+xml;base64', $qrCode);
    }

    #[Test]
    public function it_sends_only_the_required_fields(): void
    {
        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make(
                $this->fixture('/pix/create/201.json'),
                201
            ),
        ]);

        $request = new CreateRequest(amount: 10.0, description: 'Minimal');

        $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (CreateRequest $request) => $request->body()->all() === [
                'amount' => 10.0,
                'description' => 'Minimal',
            ]
        );
    }

    #[Test]
    public function it_parses_the_split_returned_by_the_api(): void
    {
        $mockResponse = json_encode(
            json_decode($this->fixture('/pix/create/201.json'), true) + [
                'split' => [['subAccount' => 'loja-centro', 'amount' => 10.5]],
            ]
        );

        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        /** @var CreateResponse $response */
        $response = $this->connector($mockClient)->send(
            new CreateRequest(amount: 10.0, description: 'Split')
        );

        $split = $response->getSplit();

        $this->assertCount(1, $split);
        $this->assertSame('loja-centro', $split[0]->subAccount);
        $this->assertSame(10.5, $split[0]->amount);
    }
}
