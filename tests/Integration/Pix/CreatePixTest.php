<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\Pix;

use MountBit\PagueDev\Dtos\Pix\Customer;
use MountBit\PagueDev\Dtos\Pix\SplitAllocation;
use MountBit\PagueDev\Requests\Pix\Create;
use MountBit\PagueDev\Requests\SubAccounts\GetList as GetSubAccounts;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class CreatePixTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_pix_charge(): void
    {
        $externalReference = $this->uniqueReference('pix');

        $response = $this->api->send(
            new Create(
                amount: 10.00,
                description: 'Integration PIX - '.$externalReference,
                customer: new Customer(
                    name: 'Integration User',
                    document: '95633291042',
                    email: 'integration@pix.test',
                    phone: '+5511999998888',
                ),
                projectId: $this->projectId,
                expiresIn: 3600,
                externalReference: $externalReference,
                metadata: ['source' => 'sdk-integration-tests'],
            )
        );

        $this->assertTrue($response->successful());

        $this->assertNotEmpty($response->getId());
        $this->assertSame('pending', $response->getStatus());
        $this->assertSame(10.0, $response->getAmount());
        $this->assertSame('BRL', $response->getCurrency());
        $this->assertNotEmpty($response->getPixCopyPaste());
        $this->assertNotEmpty($response->getExpiresAt());
        $this->assertSame($externalReference, $response->getExternalReference());

        $this->assertStringStartsWith(
            'data:image/svg+xml;base64',
            $response->getQrCode()
        );
    }

    #[Test]
    public function it_creates_a_pix_charge_with_a_split(): void
    {
        $subAccounts = $this->api->send(new GetSubAccounts)->getData();

        if (empty($subAccounts)) {
            $this->markTestSkipped('The account has no sub accounts to split with.');
        }

        $response = $this->api->send(
            new Create(
                amount: 50.00,
                description: 'Integration PIX with split',
                externalReference: $this->uniqueReference('split'),
                split: [new SplitAllocation($subAccounts[0]->reference, 5.00)],
            )
        );

        $this->assertTrue($response->successful(), $response->body());

        $split = $response->getSplit();

        $this->assertCount(1, $split);
        $this->assertSame($subAccounts[0]->reference, $split[0]->subAccount);
        $this->assertSame(5.0, $split[0]->amount);
    }

    #[Test]
    public function it_replays_the_cached_response_for_the_same_idempotency_key(): void
    {
        $idempotencyKey = $this->uniqueReference('idem');

        $request = fn () => new Create(
            amount: 10.00,
            description: 'Integration PIX idempotency',
            projectId: $this->projectId,
            idempotencyKey: $idempotencyKey,
        );

        $first = $this->api->send($request());
        $second = $this->api->send($request());

        $this->assertTrue($first->successful());
        $this->assertTrue($second->successful());
        $this->assertSame($first->getId(), $second->getId());
        $this->assertSame($first->getPixCopyPaste(), $second->getPixCopyPaste());
    }
}
