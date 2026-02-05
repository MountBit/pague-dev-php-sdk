<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Transactions;

use Saloon\Http\Response;

class GetById extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function getType(): string
    {
        return $this->json('type');
    }

    public function getPaymentMethod(): string
    {
        return $this->json('paymentMethod');
    }

    public function getAmount(): float
    {
        return (float) $this->json('amount');
    }

    public function getCurrency(): string
    {
        return $this->json('currency');
    }

    public function getcreatedAt(): string
    {
        return $this->json('createdAt');
    }

    public function getDescription(): ?string
    {
        return $this->json('description');
    }

    public function getExternalReference(): ?string
    {
        return $this->json('externalReference');
    }

    public function getCustomerId(): ?string
    {
        return $this->json('customerId');
    }

    public function getProjectId(): ?string
    {
        return $this->json('projectId');
    }

    public function getMetadata(): array
    {
        return $this->json('metadata') ?: [];
    }

    public function getPixCopyPaste(): ?string
    {
        return $this->json('pixCopyPaste');
    }

    public function getExpiresAt(): ?string
    {
        return $this->json('expiresAt');
    }

    public function getpaidAt(): ?string
    {
        return $this->json('paidAt');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->json('updatedAt');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
