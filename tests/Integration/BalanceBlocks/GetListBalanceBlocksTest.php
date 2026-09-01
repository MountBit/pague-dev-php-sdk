<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\BalanceBlocks;

use MountBit\PagueDev\Requests\BalanceBlocks\GetList;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class GetListBalanceBlocksTest extends ApiTestCase
{
    #[Test]
    public function it_lists_balance_blocks(): void
    {
        $response = $this->api->send(new GetList);

        $this->assertTrue($response->successful());
        $this->assertIsArray($response->getItems());
        $this->assertSame(count($response->getItems()), $response->getTotal());
    }

    #[Test]
    public function it_filters_balance_blocks_by_status(): void
    {
        $response = $this->api->send(new GetList(status: 'awaiting_response'));

        $this->assertTrue($response->successful());

        foreach ($response->getItems() as $block) {
            $this->assertSame('awaiting_response', $block->status);
        }
    }
}
