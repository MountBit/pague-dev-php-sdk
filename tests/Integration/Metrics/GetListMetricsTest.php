<?php

namespace MountBit\PagueDev\Tests\Integration\Metrics;

use MountBit\PagueDev\Requests\Metrics\GetList;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class GetListMetricsTest extends ApiTestCase
{
    #[Test]
    public function it_fetches_account_metrics(): void
    {
        $response = $this->api->send(new GetList);

        $this->assertTrue($response->successful());

        $this->assertIsNumeric($response->json()['totalRevenue']);
        $this->assertIsNumeric($response->json()['currentMrr']);
        $this->assertIsArray($response->json()['groupedByDay']);
    }
}
