<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Account;

use MountBit\PagueDev\Dtos\BalanceAmount;
use Saloon\Http\Response;

class Get extends Response
{
    public function getId(): string
    {
        return $this->json('account.id');
    }

    public function getStatus(): string
    {
        return $this->json('account.status');
    }

    public function getAvailableBalance(): BalanceAmount
    {
        return BalanceAmount::fromArray($this->json('balance.available') ?: []);
    }

    public function getPromotionalBalance(): BalanceAmount
    {
        return BalanceAmount::fromArray($this->json('balance.promotional') ?: []);
    }

    public function getHeldBalance(): BalanceAmount
    {
        return BalanceAmount::fromArray($this->json('balance.held') ?: []);
    }

    public function getTotalBalance(): BalanceAmount
    {
        return BalanceAmount::fromArray($this->json('balance.total') ?: []);
    }

    public function getCurrency(): string
    {
        return $this->json('balance.currency');
    }

    public function getBalanceUpdatedAt(): ?string
    {
        return $this->json('balance.updatedAt');
    }

    public function getAccount(): array
    {
        return $this->json('account') ?: [];
    }

    public function getBalance(): array
    {
        return $this->json('balance') ?: [];
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
