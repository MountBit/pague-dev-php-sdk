<?php

namespace MountBit\PagueDev\Tests\Integration\Projects;

use MountBit\PagueDev\Requests\Projects\Create;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class CreateProjectTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_project(): void
    {
        $response = $this->api->send(new Create(
            name: 'Integration Project - '.uniqid(more_entropy: true),
            color: '#000000',
            description: 'Sandbox project'
        ));

        $this->assertTrue($response->successful());
        $this->assertArrayHasKey('id', $response->json());
    }
}
