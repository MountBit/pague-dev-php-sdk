<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Transactions;

use Saloon\Http\Response;

class Refund extends Response
{
    public function getOriginalTransactionId(): string
    {
        return $this->json('originalTransactionId');
    }

    public function getPspProvider(): string
    {
        return $this->json('pspProvider');
    }

    public function getPspRefundTransactionId(): string
    {
        return $this->json('pspRefundTransactionId');
    }

    public function getStatus(): string
    {
        return $this->json('status');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
