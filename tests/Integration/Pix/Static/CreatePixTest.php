<?php

namespace MountBit\PagueDev\Tests\Integration\Pix\Static;

use MountBit\PagueDev\Requests\Pix\Static\Create;
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

        $orderId = uniqid(more_entropy: true);

        $request = new Create(
            amount: 25.00,
            description: 'Integration Static PIX - '.$orderId,
            projectId: $this->projectId,
            externalReference: 'static_pix_'.$orderId,
            metadata: [
                'orderId' => $orderId,
                'source' => 'integration-test',
            ],
        );

        $response = $this->api->send($request);

        $this->assertTrue($response->successful());

        $data = $response->json();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('pixCopyPaste', $data);
    }
}
