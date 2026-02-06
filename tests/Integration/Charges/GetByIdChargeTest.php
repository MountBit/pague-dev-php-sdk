<?php

namespace MountBit\PagueDev\Tests\Integration\Charges;

use MountBit\PagueDev\Requests\Charges\GetById;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class GetByIdChargeTest extends ApiTestCase
{
    #[Test]
    public function it_gets_a_charge_by_id(): void
    {
        if (empty($this->chargeId)) {
            $this->markTestSkipped('PAGUEDEV_SANDBOX_CHARGE_ID not set.');
        }

        $response = $this->api->send(new GetById($this->chargeId));

        $this->assertTrue($response->successful());
        $this->assertSame($this->chargeId, $response->json()['id']);
    }
}
