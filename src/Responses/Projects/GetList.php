<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Projects;

use MountBit\PagueDev\Dtos\Project;
use Saloon\Http\Response;

class GetList extends Response
{
    /**
     * @return array<Project>
     */
    public function getItems(): array
    {
        return array_map(
            fn (array $project) => new Project(
                $project['id'],
                $project['name'],
                $project['color'] ?? null,
                $project['description'] ?? null,
                $project['logoUrl'] ?? null,
                $project['createdAt'] ?? null
            ),
            $this->json('items') ?: []
        );
    }

    public function getTotal(): int
    {
        return (int) $this->json('total');
    }

    public function getPage(): int
    {
        return (int) $this->json('page');
    }

    public function getLimit(): int
    {
        return (int) $this->json('limit');
    }

    public function getTotalPages(): int
    {
        return (int) $this->json('totalPages');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
