<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

class Charge
{
    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly string $name,
        public readonly string $currency,
        public readonly string $status,
        public readonly string $slug,
        public readonly string $url,
        public readonly array $paymentMethods,
        public readonly int $maxInstallments,
        public readonly array $notifications,
        public readonly bool $allowCoupons,
        public readonly int $paymentsCount,
        public readonly float $totalCollected,
        public readonly ?string $customerId,
        public readonly array $description,
        public readonly float $amount,
        public readonly ?string $expiresAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt
    ) {}
}
