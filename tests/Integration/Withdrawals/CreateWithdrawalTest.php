<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\Withdrawals;

use MountBit\PagueDev\Requests\Withdrawals\Create;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class CreateWithdrawalTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_withdrawal(): void
    {
        if (! getenv('PAGUEDEV_RUN_WITHDRAWALS')) {
            $this->markTestSkipped(
                'PAGUEDEV_RUN_WITHDRAWALS not set. Withdrawals move balance.'
            );
        }

        $response = $this->api->send(
            new Create(
                pixKey: getenv('PAGUEDEV_PIX_KEY') ?: '95633291042',
                pixKeyType: getenv('PAGUEDEV_PIX_KEY_TYPE') ?: 'cpf',
                holderName: 'Integration User',
                holderDocument: '95633291042',
                holderDocumentType: 'cpf',
                amount: 1.00,
                projectId: $this->projectId,
                externalReference: $this->uniqueReference('saque'),
                idempotencyKey: $this->uniqueReference('saque-idem'),
            )
        );

        $this->assertTrue($response->successful());

        $this->assertNotEmpty($response->getId());
        $this->assertContains(
            $response->getStatus(),
            ['pending', 'processing', 'completed', 'failed']
        );
        $this->assertIsFloat($response->getFeeAmount());
        $this->assertIsFloat($response->getNetAmount());
    }
}
