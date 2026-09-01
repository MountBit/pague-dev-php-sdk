<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\BalanceBlocks;

use MountBit\PagueDev\Requests\BalanceBlocks\GetList as GetListRequest;
use MountBit\PagueDev\Responses\BalanceBlocks\GetList as GetListResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetListBalanceBlocksRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/balance-blocks/list/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetListRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $request = new GetListRequest(status: 'awaiting_response');

        /** @var GetListResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (GetListRequest $request) => $request->defaultQuery() === [
                'status' => 'awaiting_response',
            ]
        );

        $this->assertTrue($response instanceof GetListResponse);

        $this->assertSame($mockResponseJson, $response->toArray());
        $this->assertSame($mockResponseJson['total'], $response->getTotal());

        foreach ($mockResponseJson['items'] as $index => $item) {
            $block = $response->getItems()[$index];

            $this->assertSame($item['id'], $block->id);
            $this->assertSame($item['transactionId'], $block->transactionId);
            $this->assertSame($item['externalReference'], $block->externalReference);
            $this->assertSame($item['e2eId'], $block->e2eId);
            $this->assertSame($item['amount'], $block->amount);
            $this->assertSame($item['status'], $block->status);
            $this->assertSame($item['blockType'], $block->blockType);
            $this->assertSame($item['referenceNumber'], $block->referenceNumber);
            $this->assertSame($item['reason'], $block->reason);
            $this->assertNull($block->resolutionReason);
            $this->assertNull($block->resolvedAt);
            $this->assertSame($item['createdAt'], $block->createdAt);
        }
    }

    #[Test]
    public function it_sends_no_query_parameters_by_default(): void
    {
        $this->assertSame([], (new GetListRequest)->defaultQuery());
    }
}
