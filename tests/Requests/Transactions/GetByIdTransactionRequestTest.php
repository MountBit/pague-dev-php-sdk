<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Transactions;

use MountBit\PagueDev\Dtos\BlockedDeposit;
use MountBit\PagueDev\Dtos\BlockedDepositRefund;
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
    public function it_parses_a_deposit_blocked_by_account_policy(): void
    {
        $mockClient = new MockClient([
            GetByIdRequest::class => MockResponse::make($this->fixture('/transactions/get/200-blocked-deposit.json'), 200),
        ]);

        /** @var GetByIdResponse $response */
        $response = $this->connector($mockClient)->send(new GetByIdRequest(id: '13e3ffd1-55d9-4863-9cc1-8955475cdf64'));

        $this->assertSame('failed', $response->getStatus());
        $this->assertSame('Bloqueio de depósito: pagador CNPJ não permitido', $response->getFailureReason());

        $blockedDeposit = $response->getBlockedDeposit();

        $this->assertInstanceOf(BlockedDeposit::class, $blockedDeposit);
        $this->assertSame(BlockedDeposit::REASON_CNPJ_PAYER_NOT_ALLOWED, $blockedDeposit->reason);
        $this->assertInstanceOf(BlockedDepositRefund::class, $blockedDeposit->refund);
        $this->assertSame(BlockedDepositRefund::STATUS_CONFIRMED, $blockedDeposit->refund->status);
        $this->assertSame('D54811417202609082224L1aDrByinUX', $blockedDeposit->refund->e2eId);
        $this->assertSame('2026-09-09T20:50:14.900Z', $blockedDeposit->refund->settledAt);
    }

    #[Test]
    public function it_returns_null_when_the_deposit_was_not_blocked(): void
    {
        $mockClient = new MockClient([
            GetByIdRequest::class => MockResponse::make($this->fixture('/transactions/get/200.json'), 200),
        ]);

        /** @var GetByIdResponse $response */
        $response = $this->connector($mockClient)->send(new GetByIdRequest(id: '3c90c3cc-0d44-4b50-8888-8dd25736052a'));

        $this->assertNull($response->getBlockedDeposit());
        $this->assertNull($response->getFailureReason());
    }

    #[Test]
    public function it_keeps_the_refund_null_while_the_platform_has_not_started_it(): void
    {
        $mockClient = new MockClient([
            GetByIdRequest::class => MockResponse::make([
                'id' => '13e3ffd1-55d9-4863-9cc1-8955475cdf64',
                'status' => 'failed',
                'type' => 'payment',
                'paymentMethod' => 'pix',
                'amount' => 10.0,
                'currency' => 'BRL',
                'blockedDeposit' => ['reason' => 'cnpj_payer_not_allowed', 'refund' => null],
                'createdAt' => '2026-09-09T20:49:41.506Z',
            ], 200),
        ]);

        /** @var GetByIdResponse $response */
        $response = $this->connector($mockClient)->send(new GetByIdRequest(id: '13e3ffd1-55d9-4863-9cc1-8955475cdf64'));

        $this->assertNull($response->getBlockedDeposit()->refund);
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
