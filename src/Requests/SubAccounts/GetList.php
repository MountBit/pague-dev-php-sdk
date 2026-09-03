<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\SubAccounts;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Responses\SubAccounts\GetList as ListResponse;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

class GetList extends Request
{
    protected Method $method = Method::GET;

    protected ?string $response = ListResponse::class;

    public function resolveEndpoint(): string
    {
        return '/sub-accounts';
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->remove(Api::SUB_ACCOUNT_HEADER);
    }
}
