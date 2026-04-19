<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Account;

use Saloon\Http\Response;

class GetList extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function getCompanyName(): ?string
    {
        return $this->json('company.razaoSocial');
    }

    public function getCompanyTradeName(): ?string
    {
        return $this->json('company.nomeFantasia');
    }

    public function getCompanyCnpj(): ?string
    {
        return $this->json('company.cnpj');
    }

    public function getCompanyEmail(): ?string
    {
        return $this->json('company.email');
    }

    public function getCompanyPhone(): ?string
    {
        return $this->json('company.phone');
    }

    public function getCompanyStatus(): ?string
    {
        return $this->json('company.status');
    }

    public function getBalanceAvailableAmountFormatted(): float
    {
        return (float) $this->json('balance.available.amountFormatted');
    }

    public function getBalanceAvailableAmount(): int
    {
        return (int) $this->json('balance.available.amount');
    }

    public function getBalancePromotionalAmountFormatted(): float
    {
        return (float) $this->json('balance.promotional.amountFormatted');
    }

    public function getBalancePromotionalAmount(): int
    {
        return (int) $this->json('balance.promotional.amount');
    }

    public function getBalanceHeldAmountFormatted(): float
    {
        return (float) $this->json('balance.held.amountFormatted');
    }

    public function getBalanceHeldAmount(): int
    {
        return (int) $this->json('balance.held.amount');
    }

    public function getBalanceTotalAmountFormatted(): float
    {
        return (float) $this->json('balance.total.amountFormatted');
    }

    public function getBalanceTotalAmount(): int
    {
        return (int) $this->json('balance.total.amount');
    }

    public function getBalanceCurrency(): string
    {
        return $this->json('balance.currency');
    }

    public function getBalanceUpdatedAt(): ?string
    {
        return $this->json('balance.updatedAt');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
