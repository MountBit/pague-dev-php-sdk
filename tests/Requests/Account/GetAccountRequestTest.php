<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Account;

use MountBit\PagueDev\Requests\Account\Get as GetRequest;
use MountBit\PagueDev\Responses\Account\Get as GetResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetAccountRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/account/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        /** @var GetResponse $response */
        $response = $this->connector($mockClient)->send(new GetRequest);

        $this->assertTrue($response instanceof GetResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        $this->assertSame($mockResponseJson['account']['id'], $response->getId());
        $this->assertSame($mockResponseJson['account']['status'], $response->getStatus());
        $this->assertSame($mockResponseJson['balance']['currency'], $response->getCurrency());
        $this->assertSame(
            $mockResponseJson['balance']['updatedAt'],
            $response->getBalanceUpdatedAt()
        );

        $this->assertSame(15075, $response->getAvailableBalance()->amount);
        $this->assertSame(150.75, $response->getAvailableBalance()->amountFormatted);
        $this->assertSame(0, $response->getPromotionalBalance()->amount);
        $this->assertSame(2500, $response->getHeldBalance()->amount);
        $this->assertSame(175.75, $response->getTotalBalance()->amountFormatted);

        $this->assertSame($mockResponseJson['account'], $response->getAccount());
        $this->assertSame($mockResponseJson['balance'], $response->getBalance());
    }

    #[Test]
    public function it_resolves_the_account_endpoint(): void
    {
        $this->assertSame('/account', (new GetRequest)->resolveEndpoint());
    }
}
