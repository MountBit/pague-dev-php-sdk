<?php

namespace MountBit\PagueDev\Tests\Integration\Customers;

use MountBit\PagueDev\Requests\Customers\Create;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class CreateCustomerTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_customer(): void
    {
        $response = $this->api->send(new Create(
            name: 'Integration Customer - '.uniqid(more_entropy: true),
            document: '12345678900',
            email: 'customer@integration.test',
            phone: '+5511999999999'
        ));

        $this->assertTrue($response->successful());
        $this->assertArrayHasKey('id', $response->json());
    }
}
