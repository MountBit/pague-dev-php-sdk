<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Projects;

use MountBit\PagueDev\Requests\Projects\Create as CreateProjectRequest;
use MountBit\PagueDev\Responses\Projects\Create as CreateProjectResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreateProjectRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201(): void
    {
        $mockResponse = $this->fixture('/projects/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateProjectRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $payload = [
            'name' => 'Minha Loja',
            'color' => '#3B82F6',
            'description' => 'Loja virtual de eletrônicos',
            'logoUrl' => 'https://example.com/logo.png',
        ];

        $request = new CreateProjectRequest(
            name: $payload['name'],
            color: $payload['color'],
            description: $payload['description'],
            logoUrl: $payload['logoUrl'],
        );

        /** @var CreateProjectResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (CreateProjectRequest $request) => $request->body()->all() === $payload
        );

        $this->assertTrue($response instanceof CreateProjectResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $this->assertEquals($mockResponseJson[$key], $response->$getter());
        }
    }
}
