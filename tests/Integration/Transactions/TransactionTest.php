<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\Transactions;

use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;
use MountBit\PagueDev\Requests\Transactions\GetById;
use MountBit\PagueDev\Requests\Transactions\Refund;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class TransactionTest extends ApiTestCase
{
    #[Test]
    public function it_gets_a_transaction_by_id(): void
    {
        $charge = $this->api->send(
            new CreatePix(
                amount: 10.00,
                description: 'Integration transaction lookup',
                projectId: $this->projectId,
            )
        );

        $response = $this->api->send(new GetById(id: $charge->getId()));

        $this->assertTrue($response->successful());
        $this->assertSame($charge->getId(), $response->getId());
        $this->assertSame('payment', $response->getType());
        $this->assertSame('pix', $response->getPaymentMethod());
        $this->assertSame(10.0, $response->getAmount());
    }

    #[Test]
    public function it_gets_a_transaction_by_external_reference(): void
    {
        $externalReference = $this->uniqueReference('lookup');

        $charge = $this->api->send(
            new CreatePix(
                amount: 10.00,
                description: 'Integration external reference lookup',
                projectId: $this->projectId,
                externalReference: $externalReference,
            )
        );

        $response = $this->api->send(new GetById(id: $externalReference));

        $this->assertTrue($response->successful());
        $this->assertSame($charge->getId(), $response->getId());
    }

    #[Test]
    public function it_requests_a_refund_for_a_paid_transaction(): void
    {
        if (empty($this->transactionId)) {
            $this->markTestSkipped('PAGUEDEV_TRANSACTION_ID not set.');
        }

        $response = $this->api->send(
            new Refund(
                id: $this->transactionId,
                reason: 'Integration test refund',
            )
        );

        if ($response->status() === 400) {
            $this->markTestSkipped(
                'PAGUEDEV_TRANSACTION_ID must point to a completed payment: '
                .ApiException::fromResponse($response)->getMessage()
            );
        }

        $this->assertTrue($response->successful(), $response->body());

        $this->assertSame($this->transactionId, $response->getOriginalTransactionId());
        $this->assertNotEmpty($response->getPspProvider());
        $this->assertNotEmpty($response->getPspRefundTransactionId());
        $this->assertContains(
            $response->getStatus(),
            ['PENDING', 'CONFIRMED', 'ERROR']
        );
    }

    #[Test]
    public function it_exposes_the_pix_settlement_fields_of_a_paid_transaction(): void
    {
        if (empty($this->transactionId)) {
            $this->markTestSkipped('PAGUEDEV_TRANSACTION_ID not set.');
        }

        $response = $this->api->send(new GetById(id: $this->transactionId));

        $this->assertTrue($response->successful());

        if ($response->getStatus() !== 'completed') {
            $this->markTestSkipped('The transaction is not completed.');
        }

        $this->assertSame('payment', $response->getType());
        $this->assertNotEmpty($response->getE2eId());
        $this->assertNotEmpty($response->getPaidAt());
        $this->assertNotEmpty($response->getCounterpartName());
        $this->assertMatchesRegularExpression(
            '/^\d{11}$|^\d{14}$/',
            (string) $response->getCounterpartDocument(),
            'The counterpart document must come unmasked'
        );
    }
}
