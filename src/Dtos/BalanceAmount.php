<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class BalanceAmount
{
    public function __construct(
        public int $amount,
        public float $amountFormatted,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['amount'] ?? 0),
            (float) ($data['amountFormatted'] ?? 0),
        );
    }
}
