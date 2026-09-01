<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\BalanceBlocks;

use MountBit\PagueDev\Responses\BalanceBlocks\GetList as ListResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetList extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = ListResponse::class;

    public function __construct(
        public readonly ?string $status = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/balance-blocks';
    }

    public function defaultQuery(): array
    {
        $query = [];

        if (! empty($this->status)) {
            $query['status'] = $this->status;
        }

        return $query;
    }
}
