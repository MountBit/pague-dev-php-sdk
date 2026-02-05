<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Customers;

use Saloon\Http\Response;

class Create extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getName(): string
    {
        return $this->json('name');
    }

    public function getDocument(): string
    {
        return $this->json('document');
    }

    public function getDocumentType(): string
    {
        return $this->json('documentType');
    }

    public function getEmail(): ?string
    {
        return $this->json('email');
    }

    public function getPhone(): ?string
    {
        return $this->json('phone');
    }

    public function getCreatedAt(): ?string
    {
        return $this->json('createdAt');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
