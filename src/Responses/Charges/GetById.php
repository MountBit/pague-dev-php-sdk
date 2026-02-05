<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Charges;

use Saloon\Http\Response;

class GetById extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getProjectId(): string
    {
        return $this->json('projectId');
    }

    public function getName(): string
    {
        return $this->json('name');
    }

    public function getCurrency(): string
    {
        return $this->json('currency');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function getSlug(): string
    {
        return $this->json('slug');
    }

    public function getUrl(): string
    {
        return $this->json('url');
    }

    public function getPaymentMethods(): array
    {
        return $this->json('paymentMethods') ?: [];
    }

    public function getMaxInstallments(): int
    {
        return (int) $this->json('maxInstallments');
    }

    public function getNotifications(): array
    {
        return $this->json('notifications') ?: [];
    }

    public function getAllowCoupons(): bool
    {
        return (bool) $this->json('allowCoupons');
    }

    public function getPaymentsCount(): int
    {
        return (int) $this->json('paymentsCount');
    }

    public function getTotalCollected(): float
    {
        return (float) $this->json('totalCollected');
    }

    public function getCustomerId(): ?string
    {
        return $this->json('customerId');
    }

    public function getDescription(): array
    {
        return $this->json('description') ?: [];
    }

    public function getAmount(): float
    {
        return (float) $this->json('amount');
    }

    public function getExpiresAt(): ?string
    {
        return $this->json('expiresAt');
    }

    public function getCreatedAt(): ?string
    {
        return $this->json('createdAt');
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
