<?php

declare(strict_types=1);

namespace MountBit\PagueDev;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\HeaderAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class Api extends Connector
{
    use HasTimeout;

    public function __construct(
        public readonly string $apiKey,
        public readonly ?string $baseUrl = null,
        protected readonly int $connectTimeout = 5,
        protected readonly int $requestTimeout = 10,
    ) {}

    public function resolveBaseUrl(): string
    {
        return empty($this->baseUrl)
            ? 'https://api.pague.dev/v1/pix'
            : $this->baseUrl;
    }

    protected function defaultAuth(): ?Authenticator
    {
        return new HeaderAuthenticator($this->apiKey, 'X-API-Key');
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'pague.dev - PHP SDK',
        ];
    }
}
