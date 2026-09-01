<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class SubAccount
{
    public function __construct(
        public string $id,
        public string $reference,
        public string $name,
        public string $status,
        public ?string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['reference'],
            $data['name'],
            $data['status'],
            $data['createdAt'] ?? null,
        );
    }
}
