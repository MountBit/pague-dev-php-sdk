<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\SubAccounts;

use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;
use MountBit\PagueDev\Requests\SubAccounts\Create;
use MountBit\PagueDev\Requests\SubAccounts\GetList;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class SubAccountTest extends ApiTestCase
{
    #[Test]
    public function it_lists_sub_accounts(): void
    {
        $response = $this->api->send(new GetList);

        $this->assertTrue($response->successful());
        $this->assertIsArray($response->getData());

        foreach ($response->getData() as $subAccount) {
            $this->assertNotEmpty($subAccount->id);
            $this->assertNotEmpty($subAccount->reference);
        }
    }

    #[Test]
    public function it_creates_a_sub_account(): void
    {
        $reference = 'sdk-'.substr(bin2hex(random_bytes(8)), 0, 12);

        $response = $this->api->send(
            new Create(reference: $reference, name: 'SDK Integration Sub Account')
        );

        if ($response->status() === 403) {
            $this->markTestSkipped('Sub accounts are not enabled for this account.');
        }

        $this->assertTrue($response->successful());
        $this->assertSame($reference, $response->getReference());
        $this->assertNotEmpty($response->getId());
    }

    #[Test]
    public function it_creates_a_pix_charge_on_behalf_of_a_sub_account(): void
    {
        if (empty($this->subAccount)) {
            $this->markTestSkipped('PAGUEDEV_SUB_ACCOUNT not set.');
        }

        $response = $this->api
            ->forSubAccount($this->subAccount)
            ->send(new CreatePix(
                amount: 10.00,
                description: 'Integration PIX from sub account',
            ));

        if ($response->status() === 403) {
            $exception = ApiException::fromResponse($response);

            $this->assertTrue(
                $exception->hasErrorCode(ApiException::SUB_ACCOUNT_FORBIDDEN)
                || $exception->hasErrorCode(ApiException::SUB_ACCOUNT_SUSPENDED)
            );

            $this->markTestSkipped(
                'This credential cannot operate sub accounts: '.$exception->getMessage()
            );
        }

        $this->assertTrue($response->successful());
        $this->assertNotEmpty($response->getPixCopyPaste());
    }
}
