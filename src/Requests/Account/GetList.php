<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Account;

use MountBit\PagueDev\Responses\Account\GetList as GetAccountListResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class GetList extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::GET;

    protected ?string $response = GetAccountListResponse::class;

    public function resolveEndpoint(): string
    {
        return '/account';
    }
}
