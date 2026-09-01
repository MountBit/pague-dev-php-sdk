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
        public ?string $updatedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['color'] ?? null,
            $data['description'] ?? null,
            $data['logoUrl'] ?? null,
            $data['createdAt'] ?? null,
            $data['updatedAt'] ?? null,
        );
    }
}
