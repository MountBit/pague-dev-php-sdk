<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Withdrawals;

use LogicException;
use MountBit\PagueDev\Requests\Withdrawals\Create;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    #[Test]
    public function it_requires_bank_account_id_or_pix_fields(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('bankAccountId or Pix fields are required');

        new Create(
            amount: 100.0,
        );
    }

    #[Test]
    public function it_cannot_accept_bank_account_id_and_pix_fields_at_the_same_time(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Use bankAccountId OR Pix fields, not both at the same time');

        new Create(
            amount: 100.0,
            bankAccountId: 'bank_123',
            pixKey: '12345678901',
            pixKeyType: 'cpf',
            holderName: 'John Doe',
            holderDocument: '12345678901',
            holderDocumentType: 'cpf',
        );
    }

    #[Test]
    public function it_requires_all_pix_fields_when_using_pix(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('When using Pix fields, all of them are required');

        new Create(
            amount: 100.0,
            pixKey: '12345678901',
            pixKeyType: 'cpf',
            holderName: 'John Doe',
        );
    }

    #[Test]
    public function it_sets_bank_account_id_correctly(): void
    {
        $request = new Create(
            amount: 150.0,
            bankAccountId: 'bank_456',
        );

        $body = $request->defaultBody();

        $this->assertArrayHasKey('bankAccountId', $body);
        $this->assertSame('bank_456', $body['bankAccountId']);

        $this->assertArrayNotHasKey('pixKey', $body);
        $this->assertSame(150.0, $body['amount']);
    }

    #[Test]
    public function it_sets_pix_fields_correctly(): void
    {
        $pix = [
            'pixKey' => '12345678901',
            'pixKeyType' => 'cpf',
            'holderName' => 'Jane Doe',
            'holderDocument' => '12345678901',
            'holderDocumentType' => 'cpf',
        ];

        $request = new Create(
            amount: 200.0,
            pixKey: $pix['pixKey'],
            pixKeyType: $pix['pixKeyType'],
            holderName: $pix['holderName'],
            holderDocument: $pix['holderDocument'],
            holderDocumentType: $pix['holderDocumentType'],
        );

        $body = $request->defaultBody();

        foreach ($pix as $key => $value) {
            $this->assertArrayHasKey($key, $body);
            $this->assertSame($value, $body[$key]);
        }

        $this->assertArrayNotHasKey('bankAccountId', $body);
        $this->assertSame(200.0, $body['amount']);
    }
}
