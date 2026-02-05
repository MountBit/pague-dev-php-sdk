<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

class Project
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $color,
        public readonly ?string $description,
        public readonly ?string $logoUrl,
        public readonly ?string $createdAt
    ) {}
}
