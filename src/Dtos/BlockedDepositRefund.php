<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class BlockedDepositRefund
{
    public const string STATUS_PENDING = 'pending';

    public const string STATUS_CONFIRMED = 'confirmed';

    public const string STATUS_FAILED = 'failed';

    public function __construct(
        public string $status,
        public ?string $e2eId,
        public ?string $settledAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['status'] ?? ''),
            $data['e2eId'] ?? null,
            $data['settledAt'] ?? null,
        );
    }
}
