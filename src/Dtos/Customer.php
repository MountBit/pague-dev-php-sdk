<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class Customer
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public string $documentType,
        public string $document,
        public ?string $phone,
        public string $createdAt,
    ) {}
}
