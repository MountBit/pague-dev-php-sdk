<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Account;

use MountBit\PagueDev\Responses\Account\Get as GetResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class Get extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = GetResponse::class;

    public function resolveEndpoint(): string
    {
        return '/account';
    }
}
