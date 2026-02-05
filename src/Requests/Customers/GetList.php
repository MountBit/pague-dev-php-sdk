<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Customers;

use MountBit\PagueDev\Responses\Customers\GetList as ListResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetList extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = ListResponse::class;

    public function __construct(
        protected readonly ?int $page = null,
        protected readonly ?int $limit = null,
        protected readonly ?string $search = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/customers';
    }

    public function defaultQuery(): array
    {
        return array_filter([
            'page' => $this->page,
            'limit' => $this->limit,
            'search' => $this->search,
        ]);
    }
}
