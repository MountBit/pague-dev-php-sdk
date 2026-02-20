<?php

namespace MountBit\PagueDev\Tests\Integration\Withdrawals;

use MountBit\PagueDev\Requests\Withdrawals\Create;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class CreateWithdrawalTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_withdrawal_with_pix_fields(): void
    {
        $request = new Create(
            amount: 5.0,
            pixKey: 'teste@teste.com.br',
            pixKeyType: 'email',
            holderName: 'Teste Silva',
            holderDocument: '12345678901',
            holderDocumentType: 'cpf',
        );

        $response = $this->api->send($request);

        $this->assertTrue($response->successful());
        $this->assertArrayHasKey('id', $response->json());
    }

    #[Test]
    public function it_creates_a_withdrawal_bank_account_id(): void
    {
        if (empty($this->bankAccountId)) {
            $this->markTestSkipped('PAGUEDEV_SANDBOX_BANK_ACCOUNT_ID not set.');
        }

        $request = new Create(
            amount: 5.0,
            bankAccountId: $this->bankAccountId,
        );

        $response = $this->api->send($request);

        $this->assertTrue($response->successful());
        $this->assertArrayHasKey('id', $response->json());
    }
}
