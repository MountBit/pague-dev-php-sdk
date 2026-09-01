<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\Account;

use MountBit\PagueDev\Requests\Account\Get;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class GetAccountTest extends ApiTestCase
{
    #[Test]
    public function it_gets_the_account_and_its_balance(): void
    {
        $response = $this->api->send(new Get);

        $this->assertTrue($response->successful());

        $this->assertNotEmpty($response->getId());
        $this->assertNotEmpty($response->getStatus());
        $this->assertSame('BRL', $response->getCurrency());

        $this->assertIsInt($response->getAvailableBalance()->amount);
        $this->assertIsInt($response->getHeldBalance()->amount);
        $this->assertIsInt($response->getTotalBalance()->amount);
        $this->assertIsFloat($response->getAvailableBalance()->amountFormatted);
    }
}
