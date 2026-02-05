<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Responses\Customers;

use MountBit\PagueDev\Dtos\Customer;
use Saloon\Http\Response;

class GetList extends Response
{
    /**
     * @return array<Customer>
     */
    public function getItems(): array
    {
        return array_map(
            fn (array $item) => new Customer(
                $item['id'],
                $item['name'],
                $item['email'] ?? null,
                $item['documentType'],
                $item['document'],
                $item['phone'] ?? null,
                $item['createdAt']
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
