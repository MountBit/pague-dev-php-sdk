<?php

namespace MountBit\PagueDev\Tests\Integration\Charges;

use MountBit\PagueDev\Requests\Charges\Create;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class CreateChargeTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_charge(): void
    {
        if (empty($this->projectId)) {
            $this->markTestSkipped('PAGUEDEV_SANDBOX_PROJECT_ID not set.');
        }

        $request = new Create(
            projectId: $this->projectId,
            name: 'Integration Charge - '.uniqid(more_entropy: true),
            amount: 15.0,
            paymentMethods: ['pix'],
            allowCoupons: false,
        );

        $response = $this->api->send($request);

        $this->assertTrue($response->successful());
        $this->assertArrayHasKey('id', $response->json());
    }
}
