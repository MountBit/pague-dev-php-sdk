<?php

declare(strict_types=1);

namespace Tests\Requests\Charges;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Charges\GetById as GetByIdRequest;
use MountBit\PagueDev\Responses\Charges\GetById as GetByIdResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetByIdChargeRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200()
    {
        $mockResponse = $this->fixture('/charges/get/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetByIdRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $chargeId = 'charge_123';

        $request = new GetByIdRequest(id: $chargeId);

        /** @var GetByIdResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(
            fn (GetByIdRequest $request) => $request->resolveEndpoint() === '/charges/'.$chargeId
        );

        $this->assertTrue($response instanceof GetByIdResponse);

        $this->assertSame($mockResponseJson, $response->toArray());

        foreach (array_keys($mockResponseJson) as $key) {
            $getter = 'get'.ucfirst($key);
            $result = $response->$getter();
            $this->assertEquals($mockResponseJson[$key], $result);
        }
    }
}
