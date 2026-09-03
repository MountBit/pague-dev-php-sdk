<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Requests\Auth;

use MountBit\PagueDev\Api;
use MountBit\PagueDev\Responses\Auth\Token as TokenResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Token extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    protected ?string $response = TokenResponse::class;

    public function __construct(
        protected readonly string $clientId,
        protected readonly string $clientSecret,
    ) {}

    public function __debugInfo(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => '[redacted]',
        ];
    }

    public function __serialize(): array
    {
        return $this->__debugInfo();
    }

    public function resolveEndpoint(): string
    {
        return '/auth';
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->remove(Api::SUB_ACCOUNT_HEADER);
    }

    public function defaultBody(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}
