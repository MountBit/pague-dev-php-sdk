<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Metrics;

use MountBit\PagueDev\Responses\Metrics\GetList as ListResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetList extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = ListResponse::class;

    public function resolveEndpoint(): string
    {
        return '/metrics';
    }
}
