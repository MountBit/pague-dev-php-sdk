<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Withdrawals;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Withdrawals\Create as CreateWithdrawalRequest;
use MountBit\PagueDev\Responses\Withdrawals\Create as CreateWithdrawalResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreateWithdrawalRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201(): void
    {
        $mockResponse = $this->fixture('/withdrawals/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateWithdrawalRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $payload = [
            'pixKey' => '12345678901',
            'pixKeyType' => 'cpf',
            'holderName' => 'João da Silva',
            'holderDocument' => '12345678901',
            'holderDocumentType' => 'cpf',
            'amount' => 150.75,
            'projectId' => '3c90c3cc-0d44-4b50-8888-8dd25736052a',
            'externalReference' => 'saque-empresa-001',
        ];

        $request = new CreateWithdrawalRequest(
            pixKey: $payload['pixKey'],
            pixKeyType: $payload['pixKeyType'],
            holderName: $payload['holderName'],
            holderDocument: $payload['holderDocument'],
            holderDocumentType: $payload['holderDocumentType'],
            amount: $payload['amount'],
            projectId: $payload['projectId'],
            externalReference: $payload['externalReference'],
            idempotencyKey: 'saque-empresa-001',
        );

        /** @var CreateWithdrawalResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (CreateWithdrawalRequest $request) => $request->body()->all() === $payload
        );

        $mockClient->assertSent(
            fn ($request, $response) => $response
                ->getPendingRequest()
                ->headers()
                ->get(Api::IDEMPOTENCY_KEY_HEADER) === 'saque-empresa-001'
        );

        $this->assertTrue($response instanceof CreateWithdrawalResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $this->assertEquals($mockResponseJson[$key], $response->$getter());
        }
    }

    #[Test]
    public function it_sends_the_net_amount_when_provided(): void
    {
        $request = new CreateWithdrawalRequest(
            pixKey: '12345678901',
            pixKeyType: 'cpf',
            holderName: 'João da Silva',
            holderDocument: '12345678901',
            holderDocumentType: 'cpf',
            netAmount: 150.0,
        );

        $body = $request->defaultBody();

        $this->assertSame(150.0, $body['netAmount']);
        $this->assertArrayNotHasKey('amount', $body);
    }
}
