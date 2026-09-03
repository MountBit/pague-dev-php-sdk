<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\SubAccounts;

use MountBit\PagueDev\Requests\SubAccounts\Create as CreateRequest;
use MountBit\PagueDev\Responses\SubAccounts\Create as CreateResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreateSubAccountRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201(): void
    {
        $mockResponse = $this->fixture('/sub-accounts/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $payload = [
            'reference' => 'loja-centro',
            'name' => 'Loja Centro',
        ];

        $request = new CreateRequest(
            reference: $payload['reference'],
            name: $payload['name'],
        );

        /** @var CreateResponse $response */
        $response = $this->connector($mockClient)->send($request);

        $mockClient->assertSent(
            fn (CreateRequest $request) => $request->body()->all() === $payload
        );

        $this->assertTrue($response instanceof CreateResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);

            $this->assertEquals($mockResponseJson[$key], $response->$getter());
        }

        $this->assertSame($mockResponseJson['reference'], $response->getSubAccount()->reference);
    }
}
