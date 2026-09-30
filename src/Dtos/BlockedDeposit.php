<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class BlockedDeposit
{
    public const string REASON_CNPJ_PAYER_NOT_ALLOWED = 'cnpj_payer_not_allowed';

    public function __construct(
        public string $reason,
        public ?BlockedDepositRefund $refund,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['reason'] ?? ''),
            is_array($data['refund'] ?? null) ? BlockedDepositRefund::fromArray($data['refund']) : null,
        );
    }
}
