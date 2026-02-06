<?php

namespace MountBit\PagueDev\Tests\Integration\Customers;

use MountBit\PagueDev\Requests\Customers\GetList;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class GetListCustomerTest extends ApiTestCase
{
    #[Test]
    public function it_lists_customers(): void
    {
        $response = $this->api->send(new GetList(page: 1, limit: 5));

        $this->assertTrue($response->successful());
        $this->assertIsArray($response->json()['items']);
    }
}
