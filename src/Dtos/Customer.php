<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

class Customer
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly string $documentType,
        public readonly string $document,
        public readonly ?string $phone,
        public readonly string $createdAt
    ) {}
}
