<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Pix;

use Saloon\Http\Response;

class Create extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function getAmount(): float
    {
        return (float) $this->json('amount');
    }

    public function getCurrency(): string
    {
        return $this->json('currency');
    }

    public function getPixCopyPaste(): string
    {
        return $this->json('pixCopyPaste');
    }

    public function getExpiresAt(): string
    {
        return $this->json('expiresAt');
    }

    public function getCustomerId(): string
    {
        return $this->json('customerId');
    }

    public function getCreatedAt(): string
    {
        return $this->json('createdAt');
    }

    public function getExternalReference(): ?string
    {
        return $this->json('externalReference');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
