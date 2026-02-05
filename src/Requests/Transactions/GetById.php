<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Transactions;

use MountBit\PagueDev\Responses\Transactions\GetById as TransactionsGetById;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetById extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = TransactionsGetById::class;

    public function __construct(
        public readonly string $id
    ) {}

    public function resolveEndpoint(): string
    {
        return '/transactions/'.$this->id;
    }
}
