<?php

namespace MountBit\PagueDev\Tests\Integration\Pix;

use MountBit\PagueDev\Dtos\Pix\Customer;
use MountBit\PagueDev\Requests\Pix\Create;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class CreatePixTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_pix_charge(): void
    {
        if (empty($this->projectId)) {
            $this->markTestSkipped('PAGUEDEV_SANDBOX_PROJECT_ID not set.');
        }

        $request = new Create(
            amount: 10.00,
            description: 'Integration PIX - '.uniqid(more_entropy: true),
            projectId: $this->projectId,
            customer: new Customer(
                name: 'Integration User - '.uniqid(more_entropy: true),
                document: '95633291042',
                email: 'integration@pix.test',
                phone: '+5511999998888'
            ),
            expiresIn: 3600,
            externalReference: 'pix_integration_'.uniqid(more_entropy: true),
        );

        $response = $this->api->send($request);

        $this->assertTrue($response->successful());

        $data = $response->json();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('pixCopyPaste', $data);
    }
}
