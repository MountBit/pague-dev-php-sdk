<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\SubAccounts;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Responses\SubAccounts\Create as CreateResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Create extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    protected ?string $response = CreateResponse::class;

    public function __construct(
        protected readonly string $reference,
        protected readonly string $name,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/sub-accounts';
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->remove(Api::SUB_ACCOUNT_HEADER);
    }

    public function defaultBody(): array
    {
        return [
            'reference' => $this->reference,
            'name' => $this->name,
        ];
    }
}
