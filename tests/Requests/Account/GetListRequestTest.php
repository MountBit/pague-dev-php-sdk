<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Requests\Account;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Requests\Account\GetList as GetAccountListRequest;
use MountBit\PagueDev\Responses\Account\GetList as GetAccountListResponse;
use MountBit\PagueDev\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GetListRequestTest extends TestCase
{
    #[Test]
    public function it_sends_the_request_and_parses_the_response_successfully_when_status_is_200(): void
    {
        $mockResponse = $this->fixture('/account/list/200.json');

        $mockResponseJson = json_decode($mockResponse, true);

        $mockClient = new MockClient([
            GetAccountListRequest::class => MockResponse::make($mockResponse, 200),
        ]);

        $connector = (new Api('test'))->withMockClient($mockClient);

        $request = new GetAccountListRequest();

        /** @var GetAccountListResponse $response */
        $response = $connector->send($request);

        $mockClient->assertSent(fn (GetAccountListRequest $request) => true);

        $this->assertTrue($response instanceof GetAccountListResponse);

        foreach ($mockResponseJson['company'] as $companyKey => $companyValue) {
            $companyGetter = match($companyKey) {
                'razaoSocial' => 'getCompanyName',
                'nomeFantasia' => 'getCompanyTradeName',
                'cnpj' => 'getCompanyCnpj',
                'email' => 'getCompanyEmail',
                'phone' => 'getCompanyPhone',
                'status' => 'getCompanyStatus',
            };
            $this->assertEquals($companyValue, $response->$companyGetter());
        }

        foreach ($mockResponseJson['balance'] as $balanceKey => $balanceValue) {
            if ($balanceKey === 'available') {
                foreach ($balanceValue as $subKey => $subValue) {
                    if ($subKey === 'amount') {
                        $this->assertEquals($subValue, $response->getBalanceAvailableAmount());
                    } else {
                        $this->assertEquals($subValue, $response->getBalanceAvailableAmountFormatted());
                    }
                }
            } elseif ($balanceKey === 'promotional') {
                foreach ($balanceValue as $subKey => $subValue) {
                    if ($subKey === 'amount') {
                        $this->assertEquals($subValue, $response->getBalancePromotionalAmount());
                    } else {
                        $this->assertEquals($subValue, $response->getBalancePromotionalAmountFormatted());
                    }
                }
            } elseif ($balanceKey === 'held') {
                foreach ($balanceValue as $subKey => $subValue) {
                    if ($subKey === 'amount') {
                        $this->assertEquals($subValue, $response->getBalanceHeldAmount());
                    } else {
                        $this->assertEquals($subValue, $response->getBalanceHeldAmountFormatted());
                    }
                }
            } elseif ($balanceKey === 'total') {
                foreach ($balanceValue as $subKey => $subValue) {
                    if ($subKey === 'amount') {
                        $this->assertEquals($subValue, $response->getBalanceTotalAmount());
                    } else {
                        $this->assertEquals($subValue, $response->getBalanceTotalAmountFormatted());
                    }
                }
            } elseif ($balanceKey === 'currency') {
                $this->assertEquals($balanceValue, $response->getBalanceCurrency());
            } elseif ($balanceKey === 'updatedAt') {
                $this->assertEquals($balanceValue, $response->getBalanceUpdatedAt());
            }
        }
    }
}
