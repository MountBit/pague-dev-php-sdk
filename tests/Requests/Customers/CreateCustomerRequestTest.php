<?php

declare(strict_types=1);

namespace Tests\Requests\Customers;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Customers\Create as CreateCustomerRequest;
use MountBit\PagueDev\Responses\Customers\Create as CreateCustomerResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreateCustomerRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201()
    {
        $mockResponse = $this->fixture('/customers/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateCustomerRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $payload = [
            'name' => 'John Doe',
            'document' => '12345678900',
            'email' => 'john@example.com',
            'phone' => '+5511999999999',
        ];

        $request = new CreateCustomerRequest(
            name: $payload['name'],
            document: $payload['document'],
            email: $payload['email'],
            phone: $payload['phone']
        );

        /** @var CreateCustomerResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (CreateCustomerRequest $request) => $request->body()->all() === $payload
        );

        $this->assertTrue($response instanceof CreateCustomerResponse);

        $this->assertSame($mockResponseJson, $response->json());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);
            $result = $response->$getter();
            $this->assertSame($mockResponseJson[$key], $result);
        }
    }
}
