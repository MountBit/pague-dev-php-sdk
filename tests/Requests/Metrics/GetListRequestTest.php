<?php

declare(strict_types=1);

namespace Tests\Requests\Metrics;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Metrics\GetList as GetListRequest;
use MountBit\PagueDev\Responses\Metrics\GetList as GetListResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetListRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/metrics/list/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetListRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $request = new GetListRequest;

        /** @var GetListResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (GetListRequest $request) => $request->resolveEndpoint() === '/metrics'
        );

        $this->assertInstanceOf(GetListResponse::class, $response);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);
            $result = $response->$getter();
            $this->assertSame($mockResponseJson[$key], $result);
        }
    }
}
