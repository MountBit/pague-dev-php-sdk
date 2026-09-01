<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration;

use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Requests\Pix\Create as CreatePix;
use MountBit\PagueDev\Requests\Withdrawals\Create as CreateWithdrawal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class ApiMessagesTest extends ApiTestCase
{
    #[Test]
    public function it_forwards_the_validation_message_returned_by_the_api(): void
    {
        $response = $this->api->send(
            new CreatePix(amount: 0.01, description: '')
        );

        $this->assertTrue($response->failed());

        $exception = ApiException::fromResponse($response);

        $body = json_decode($response->body(), true);

        $expected = is_array($body['message'] ?? null)
            ? implode(', ', $body['message'])
            : $body['message'];

        $this->assertSame($expected, $exception->getMessage());
    }

    #[Test]
    public function it_forwards_the_message_when_both_amounts_are_sent(): void
    {
        $response = $this->api->send(
            new CreateWithdrawal(
                pixKey: '95633291042',
                pixKeyType: 'cpf',
                holderName: 'Integration User',
                holderDocument: '95633291042',
                holderDocumentType: 'cpf',
                amount: 1.00,
                netAmount: 1.00,
            )
        );

        $this->assertTrue($response->failed());

        $body = json_decode($response->body(), true);

        $this->assertNotEmpty($body['message']);

        $exception = ApiException::fromResponse($response);

        $this->assertNotEmpty($exception->getMessage());
        $this->assertNotEmpty($exception->getTraceId());
    }
}
