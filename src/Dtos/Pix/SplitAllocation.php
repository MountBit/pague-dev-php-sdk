<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos\Pix;

readonly class SplitAllocation
{
    public const string PRINCIPAL_REFERENCE = 'principal';

    public function __construct(
        public string $subAccount,
        public float $amount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['subAccount'] ?? ''),
            (float) ($data['amount'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'subAccount' => $this->subAccount,
            'amount' => $this->amount,
        ];
    }
}
