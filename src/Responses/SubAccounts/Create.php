<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\SubAccounts;

use MountBit\PagueDev\Dtos\SubAccount;
use Saloon\Http\Response;

class Create extends Response
{
    public function getId(): string
    {
        return $this->json('id');
    }

    public function getReference(): string
    {
        return $this->json('reference');
    }

    public function getName(): string
    {
        return $this->json('name');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function getCreatedAt(): ?string
    {
        return $this->json('createdAt');
    }

    public function getSubAccount(): SubAccount
    {
        return SubAccount::fromArray($this->json() ?: []);
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
