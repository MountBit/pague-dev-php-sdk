<?php

declare(strict_types=1);

namespace Tests\Requests\Charges;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Charges\GetList as GetListRequest;
use MountBit\PagueDev\Responses\Charges\GetList as GetListResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetListChargeRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200()
    {
        $mockResponse = $this->fixture('/charges/list/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetListRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $payload = [
            'page' => 1,
            'limit' => 10,
            'search' => 'Test Charge',
            'status' => 'active',
            'projectId' => 'proj_123',
        ];

        $request = new GetListRequest(
            page: $payload['page'],
            limit: $payload['limit'],
            search: $payload['search'],
            status: $payload['status'],
            projectId: $payload['projectId']
        );

        /** @var GetListResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (GetListRequest $request) => $request->defaultQuery() === $payload
        );

        $this->assertTrue($response instanceof GetListResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);
            $result = $response->$getter();

            if ($key === 'items') {
                foreach ($mockResponseJson['items'] as $index => $item) {
                    $this->assertEquals($item, (array) $result[$index]);
                }
            } else {
                $this->assertEquals($mockResponseJson[$key], $result);
            }
        }
    }
}
