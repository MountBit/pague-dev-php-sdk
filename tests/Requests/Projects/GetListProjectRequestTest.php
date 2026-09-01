<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Projects;

use MountBit\PagueDev\Requests\Projects\GetList as GetProjectsListRequest;
use MountBit\PagueDev\Responses\Projects\GetList as GetProjectsListResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetListProjectRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/projects/list/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetProjectsListRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $query = [
            'page' => 1,
            'limit' => 10,
            'sortBy' => 'createdAt',
            'sortOrder' => 'desc',
        ];

        $request = new GetProjectsListRequest(
            page: $query['page'],
            limit: $query['limit'],
            sortBy: $query['sortBy'],
            sortOrder: $query['sortOrder'],
        );

        /** @var GetProjectsListResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (GetProjectsListRequest $request) => $request->defaultQuery() === $query
        );

        $this->assertTrue($response instanceof GetProjectsListResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        $this->assertSame($mockResponseJson['total'], $response->getTotal());
        $this->assertSame($mockResponseJson['page'], $response->getPage());
        $this->assertSame($mockResponseJson['limit'], $response->getLimit());
        $this->assertSame($mockResponseJson['totalPages'], $response->getTotalPages());

        foreach ($mockResponseJson['items'] as $index => $item) {
            $project = $response->getItems()[$index];

            $this->assertSame($item['id'], $project->id);
            $this->assertSame($item['name'], $project->name);
            $this->assertSame($item['color'], $project->color);
            $this->assertSame($item['description'], $project->description);
            $this->assertSame($item['logoUrl'], $project->logoUrl);
            $this->assertSame($item['createdAt'], $project->createdAt);
            $this->assertNull($project->updatedAt);
        }
    }

    #[Test]
    public function it_sends_no_query_parameters_by_default(): void
    {
        $this->assertSame([], (new GetProjectsListRequest)->defaultQuery());
    }
}
