<?php

declare(strict_types=1);

namespace MountBit\PagueDev;

use GuzzleHttp\Utils;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\HeaderAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class Api extends Connector
{
    use HasTimeout;

    private const string DEFAULT_USER_AGENT = 'pague.dev - PHP SDK';

    private const int DEFAULT_CONNECT_TIMEOUT_IN_SECONDS = 5;

    private const int DEFAULT_REQUEST_TIMEOUT_IN_SECONDS = 10;

    public function __construct(
        public readonly string $apiKey,
        public readonly ?string $baseUrl = null,
        public readonly ?string $userAgent = self::DEFAULT_USER_AGENT,
        public readonly ?array $extraHeaders = [],
        protected readonly int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT_IN_SECONDS,
        protected readonly int $requestTimeout = self::DEFAULT_REQUEST_TIMEOUT_IN_SECONDS,
    ) {}

    public function resolveBaseUrl(): string
    {
        return empty($this->baseUrl)
            ? 'https://api.pague.dev/v1'
            : $this->baseUrl;
    }

    protected function defaultAuth(): ?Authenticator
    {
        return new HeaderAuthenticator($this->apiKey, 'X-API-Key');
    }

    protected function defaultHeaders(): array
    {
        $headers = empty($this->extraHeaders)
            ? []
            : $this->extraHeaders;

        return array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => empty($this->userAgent)
                ? $this->getDefaultUserAgent()
                : $this->userAgent,
        ]);
    }

    private function getDefaultUserAgent(): string
    {
        return sprintf(
            '%s (%s)',
            self::DEFAULT_USER_AGENT,
            Utils::defaultUserAgent(),
        );
    }
}
