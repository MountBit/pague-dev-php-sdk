<?php

namespace MountBit\PagueDev\Tests\Integration\Account;

use MountBit\PagueDev\Requests\Account\GetList;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class GetListAccountTest extends ApiTestCase
{
    #[Test]
    public function it_fetches_account_info(): void
    {
        $response = $this->api->send(new GetList);

        $this->assertTrue($response->successful());

        $this->assertIsString($response->json()['id']);
        $this->assertIsString($response->json()['status']);

        $this->assertIsString($response->json()['company']['razaoSocial']);
        $this->assertIsString($response->json()['company']['nomeFantasia']);
        $this->assertIsString($response->json()['company']['cnpj']);
        $this->assertIsString($response->json()['company']['email']);
        $this->assertIsString($response->json()['company']['phone']);
        $this->assertIsString($response->json()['company']['status']);

        $this->assertIsNumeric($response->json()['balance']['available']['amountFormatted']);
        $this->assertIsNumeric($response->json()['balance']['promotional']['amountFormatted']);
        $this->assertIsNumeric($response->json()['balance']['held']['amountFormatted']);
        $this->assertIsNumeric($response->json()['balance']['total']['amountFormatted']);
        $this->assertIsString($response->json()['balance']['currency']);
        $this->assertIsString($response->json()['balance']['updatedAt']);
    }
}
