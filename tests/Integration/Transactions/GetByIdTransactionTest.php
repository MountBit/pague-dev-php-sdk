<?php

namespace MountBit\PagueDev\Tests\Integration\Transactions;

use MountBit\PagueDev\Requests\Transactions\GetById;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class GetByIdTransactionTest extends ApiTestCase
{
    #[Test]
    public function it_gets_a_transaction_by_id(): void
    {
        if (empty($this->transactionId)) {
            $this->markTestSkipped('PAGUEDEV_SANDBOX_TRANSACTION_ID not set.');
        }

        $response = $this->api->send(new GetById($this->transactionId));

        $this->assertTrue($response->successful());
        $this->assertSame($this->transactionId, $response->json()['id']);
    }
}
