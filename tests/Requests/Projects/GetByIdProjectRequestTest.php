<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Projects;

use MountBit\PagueDev\Requests\Projects\GetById as GetByIdRequest;
use MountBit\PagueDev\Responses\Projects\GetById as GetByIdResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetByIdProjectRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/projects/get/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetByIdRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $request = new GetByIdRequest(id: $mockResponseJson['id']);

        /** @var GetByIdResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $this->assertTrue($response instanceof GetByIdResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $this->assertEquals($mockResponseJson[$key], $response->$getter());
        }

        $project = $response->getProject();

        $this->assertSame($mockResponseJson['id'], $project->id);
        $this->assertSame($mockResponseJson['updatedAt'], $project->updatedAt);
    }

    #[Test]
    public function it_encodes_the_project_id_in_the_endpoint(): void
    {
        $request = new GetByIdRequest(id: 'proj/../account');

        $this->assertSame(
            '/projects/proj%2F..%2Faccount',
            $request->resolveEndpoint()
        );
    }
}
