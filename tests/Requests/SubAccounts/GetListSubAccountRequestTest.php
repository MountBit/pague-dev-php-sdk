<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\SubAccounts;

use MountBit\PagueDev\Requests\SubAccounts\GetList as GetListRequest;
use MountBit\PagueDev\Responses\SubAccounts\GetList as GetListResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetListSubAccountRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/sub-accounts/list/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetListRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        /** @var GetListResponse $response */
        $response = $this->connector($mockClient)->send(new GetListRequest);

        $this->assertTrue($response instanceof GetListResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach ($mockResponseJson['data'] as $index => $item) {
            $subAccount = $response->getData()[$index];

            $this->assertSame($item['id'], $subAccount->id);
            $this->assertSame($item['reference'], $subAccount->reference);
            $this->assertSame($item['name'], $subAccount->name);
            $this->assertSame($item['status'], $subAccount->status);
            $this->assertSame($item['createdAt'], $subAccount->createdAt);
        }
    }

    #[Test]
    public function it_resolves_the_sub_accounts_endpoint(): void
    {
        $this->assertSame('/sub-accounts', (new GetListRequest)->resolveEndpoint());
    }
}
