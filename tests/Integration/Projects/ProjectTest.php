<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration\Projects;

use MountBit\PagueDev\Requests\Projects\Create;
use MountBit\PagueDev\Requests\Projects\GetById;
use MountBit\PagueDev\Requests\Projects\GetList;
use MountBit\PagueDev\Tests\Integration\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('integration')]
class ProjectTest extends ApiTestCase
{
    #[Test]
    public function it_creates_a_project(): void
    {
        $response = $this->api->send(
            new Create(
                name: 'SDK Integration '.uniqid(),
                color: '#3B82F6',
                description: 'Projeto criado pelos testes de integração do SDK',
            )
        );

        $this->assertTrue($response->successful());
        $this->assertNotEmpty($response->getId());
        $this->assertSame('#3B82F6', $response->getColor());
    }

    #[Test]
    public function it_lists_projects(): void
    {
        $response = $this->api->send(new GetList(page: 1, limit: 5, sortBy: 'createdAt'));

        $this->assertTrue($response->successful());
        $this->assertSame(1, $response->getPage());
        $this->assertSame(5, $response->getLimit());
        $this->assertIsArray($response->getItems());

        foreach ($response->getItems() as $project) {
            $this->assertNotEmpty($project->id);
            $this->assertNotEmpty($project->name);
        }
    }

    #[Test]
    public function it_gets_a_project_by_id(): void
    {
        $listed = $this->api->send(new GetList(limit: 1))->getItems();

        if (empty($listed)) {
            $this->markTestSkipped('The sandbox account has no projects.');
        }

        $response = $this->api->send(new GetById(id: $listed[0]->id));

        $this->assertTrue($response->successful());
        $this->assertSame($listed[0]->id, $response->getId());
        $this->assertSame($listed[0]->name, $response->getName());
    }
}
