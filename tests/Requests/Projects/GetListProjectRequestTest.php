<?php

declare(strict_types=1);

namespace Tests\Requests\Projects;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Projects\GetList as GetProjectsListRequest;
use MountBit\PagueDev\Responses\Projects\GetList as GetProjectsListResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetListProjectRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200()
    {
        $mockResponse = $this->fixture('/projects/list/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetProjectsListRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $query = [
            'page' => 1,
            'limit' => 10,
            'search' => 'Test Project',
        ];

        $request = new GetProjectsListRequest(
            page: $query['page'],
            limit: $query['limit'],
            search: $query['search']
        );

        /** @var GetProjectsListResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (GetProjectsListRequest $request) => $request->defaultQuery() === $query
        );

        $this->assertTrue($response instanceof GetProjectsListResponse);

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
