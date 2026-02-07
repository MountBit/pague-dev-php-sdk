<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class Project
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $color,
        public ?string $description,
        public ?string $logoUrl,
        public ?string $createdAt,
    ) {}
}
