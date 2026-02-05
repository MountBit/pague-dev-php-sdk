<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Projects;

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

    public function getColor(): ?string
    {
        return $this->json('color');
    }

    public function getDescription(): ?string
    {
        return $this->json('description');
    }

    public function getLogoUrl(): ?string
    {
        return $this->json('logoUrl');
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
