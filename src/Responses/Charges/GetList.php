<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Charges;

use MountBit\PagueDev\Dtos\Charge;
use Saloon\Http\Response;

class GetList extends Response
{
    /**
     * @return array<Charge>
     */
    public function getItems(): array
    {
        return array_map(
            fn (array $item) => new Charge(
                $item['id'],
                $item['projectId'],
                $item['name'],
                $item['currency'],
                $item['status'],
                $item['slug'],
                $item['url'],
                $item['paymentMethods'] ?? [],
                $item['maxInstallments'] ?? 0,
                $item['notifications'] ?? [],
                (bool) ($item['allowCoupons'] ?? false),
                (int) ($item['paymentsCount'] ?? 0),
                (float) ($item['totalCollected'] ?? 0),
                $item['customerId'] ?? null,
                $item['description'] ?? [],
                (float) $item['amount'],
                $item['expiresAt'] ?? null,
                $item['createdAt'] ?? null,
                $item['updatedAt'] ?? null
            ),
            $this->json('items') ?: []
        );
    }

    public function getTotal(): int
    {
        return (int) $this->json('total');
    }

    public function getPage(): int
    {
        return (int) $this->json('page');
    }

    public function getLimit(): int
    {
        return (int) $this->json('limit');
    }

    public function getTotalPages(): int
    {
        return (int) $this->json('totalPages');
    }

    public function toArray(): array
    {
        return $this->json();
    }
}
