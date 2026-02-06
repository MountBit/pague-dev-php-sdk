<?php

declare(strict_types=1);

namespace Tests\Requests\Charges;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Charges\Create as CreateRequest;
use MountBit\PagueDev\Responses\Charges\Create as CreateResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CreateChargeRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_201()
    {
        $mockResponse = $this->fixture('/charges/create/201.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            CreateRequest::class => MockResponse::make($mockResponse, 201),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $payload = [
            'projectId' => 'proj_123',
            'name' => 'Test Charge',
            'amount' => 150.0,
            'paymentMethods' => ['pix'],
            'customerId' => 'cust_123',
            'allowCoupons' => true,
            'notifications' => ['email'],
        ];

        $request = new CreateRequest(
            projectId: $payload['projectId'],
            name: $payload['name'],
            amount: $payload['amount'],
            paymentMethods: $payload['paymentMethods'],
            customerId: $payload['customerId'],
            allowCoupons: $payload['allowCoupons'],
            notifications: $payload['notifications'],
        );

        /** @var CreateResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (CreateRequest $request) => $request->body()->all() === $payload
        );

        $this->assertTrue($response instanceof CreateResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);
            $result = $response->$getter();
            $this->assertEquals($mockResponseJson[$key], $result);
        }
    }
}
