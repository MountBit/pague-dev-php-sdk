<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class Charge
{
    public function __construct(
        public string $id,
        public string $projectId,
        public string $name,
        public string $currency,
        public string $status,
        public string $slug,
        public string $url,
        public array $paymentMethods,
        public int $maxInstallments,
        public array $notifications,
        public bool $allowCoupons,
        public int $paymentsCount,
        public float $totalCollected,
        public ?string $customerId,
        public array $description,
        public float $amount,
        public ?string $expiresAt,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}
}
