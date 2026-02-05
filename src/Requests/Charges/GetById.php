<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Charges;

use MountBit\PagueDev\Responses\Charges\GetById as ChargesGetById;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetById extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = ChargesGetById::class;

    public function __construct(
        public readonly string $id
    ) {}

    public function resolveEndpoint(): string
    {
        return '/charges/'.$this->id;
    }
}
