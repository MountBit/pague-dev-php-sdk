<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class BalanceBlock
{
    public function __construct(
        public string $id,
        public string $transactionId,
        public float $amount,
        public string $status,
        public string $blockType,
        public string $referenceNumber,
        public string $reason,
        public ?string $externalReference,
        public ?string $e2eId,
        public ?string $resolutionReason,
        public ?string $resolvedAt,
        public ?string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['transactionId'],
            (float) ($data['amount'] ?? 0),
            $data['status'],
            $data['blockType'],
            $data['referenceNumber'],
            $data['reason'],
            $data['externalReference'] ?? null,
            $data['e2eId'] ?? null,
            $data['resolutionReason'] ?? null,
            $data['resolvedAt'] ?? null,
            $data['createdAt'] ?? null,
        );
    }
}
