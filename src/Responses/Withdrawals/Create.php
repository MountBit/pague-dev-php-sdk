<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Withdrawals;

use Saloon\Http\Response;

class Create extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getBankAccountId(): ?string
    {
        return $this->json('bankAccountId');
    }

    public function getAmount(): float
    {
        return (float) $this->json('amount');
    }

    public function getFeeAmount(): float
    {
        return (float) $this->json('feeAmount');
    }

    public function getNetAmount(): float
    {
        return (float) $this->json('netAmount');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function getSnapshotHolderName(): ?string
    {
        return $this->json('snapshotHolderName');
    }

    public function getSnapshotHolderDocument(): ?string
    {
        return $this->json('snapshotHolderDocument');
    }

    public function getSnapshotPixKey(): ?string
    {
        return $this->json('snapshotPixKey');
    }

    public function getSnapshotPixKeyType(): ?string
    {
        return $this->json('snapshotPixKeyType');
    }

    public function getFailureReason(): ?string
    {
        return $this->json('failureReason');
    }

    public function getPspReference(): ?string
    {
        return $this->json('pspReference');
    }

    public function getProcessedAt(): ?string
    {
        return $this->json('processedAt');
    }

    public function getCreatedAt(): ?string
    {
        return $this->json('createdAt');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
