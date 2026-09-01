<?php

declare(strict_types=1);

namespace MountBit\PagueDev;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use MountBit\PagueDev\Exceptions\ApiException;
use MountBit\PagueDev\Exceptions\AuthenticationFailed;
use MountBit\PagueDev\Exceptions\InvalidBaseUrl;
use MountBit\PagueDev\Exceptions\MissingCredentials;
use MountBit\PagueDev\Requests\Auth\Token as TokenRequest;
use MountBit\PagueDev\Responses\Auth\Token as TokenResponse;
use Saloon\Contracts\Authenticator;
use Saloon\Enums\PipeOrder;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\HasTimeout;
use Throwable;

class Api extends Connector
{
    use HasTimeout;

    public const string DEFAULT_BASE_URL = 'https://api-gateway.pague.dev/v2';

    public const string SUB_ACCOUNT_HEADER = 'X-Sub-Account';

    public const string IDEMPOTENCY_KEY_HEADER = 'Idempotency-Key';

    public const int IDEMPOTENCY_KEY_MIN_LENGTH = 8;

    public const int IDEMPOTENCY_KEY_MAX_LENGTH = 255;

    private const string DEFAULT_USER_AGENT = 'pague.dev - PHP SDK';

    private const int DEFAULT_CONNECT_TIMEOUT_IN_SECONDS = 5;

    private const int DEFAULT_REQUEST_TIMEOUT_IN_SECONDS = 10;

    private const int TOKEN_EXPIRATION_LEEWAY_IN_SECONDS = 30;

    private const string REDACTED = '[redacted]';

    private ?string $accessToken = null;

    private ?int $accessTokenExpiresAt = null;

    private bool $requestingAccessToken = false;

    public function __construct(
        public readonly ?string $clientId = null,
        private readonly ?string $clientSecret = null,
        public readonly ?string $baseUrl = null,
        public readonly ?string $subAccount = null,
        ?string $accessToken = null,
        ?int $accessTokenExpiresIn = null,
        public readonly bool $throwOnErrors = false,
        public readonly ?string $userAgent = self::DEFAULT_USER_AGENT,
        public readonly ?array $extraHeaders = [],
        protected readonly int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT_IN_SECONDS,
        protected readonly int $requestTimeout = self::DEFAULT_REQUEST_TIMEOUT_IN_SECONDS,
    ) {
        if (($this->clientId === null || $this->clientId === '') !== ($this->clientSecret === null || $this->clientSecret === '')) {
            throw MissingCredentials::create();
        }

        if (
            ($this->clientId === null || $this->clientId === '')
            && ($accessToken === null || $accessToken === '')
        ) {
            throw MissingCredentials::create();
        }

        $this->guardBaseUrl();

        if ($accessToken !== null && $accessToken !== '') {
            $this->setAccessToken($accessToken, $accessTokenExpiresIn);
        }
    }

    public function __debugInfo(): array
    {
        return [
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret === null ? null : self::REDACTED,
            'accessToken' => $this->accessToken === null ? null : self::REDACTED,
            'accessTokenExpiresAt' => $this->accessTokenExpiresAt,
            'baseUrl' => $this->resolveBaseUrl(),
            'subAccount' => $this->subAccount,
            'throwOnErrors' => $this->throwOnErrors,
            'userAgent' => $this->userAgent,
        ];
    }

    public function __serialize(): array
    {
        return $this->__debugInfo();
    }

    public function resolveBaseUrl(): string
    {
        return empty($this->baseUrl)
            ? self::DEFAULT_BASE_URL
            : $this->baseUrl;
    }

    public function forSubAccount(?string $subAccount): self
    {
        return new self(
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            baseUrl: $this->baseUrl,
            subAccount: $subAccount,
            accessToken: $this->accessToken,
            accessTokenExpiresIn: $this->accessTokenExpiresAt === null
                ? null
                : $this->accessTokenExpiresAt - time(),
            throwOnErrors: $this->throwOnErrors,
            userAgent: $this->userAgent,
            extraHeaders: $this->extraHeaders,
            connectTimeout: $this->connectTimeout,
            requestTimeout: $this->requestTimeout,
        );
    }

    /**
     * @throws AuthenticationFailed|MissingCredentials
     */
    public function getAccessToken(bool $forceRefresh = false): string
    {
        if (! $forceRefresh && $this->accessToken !== null && ! $this->accessTokenHasExpired()) {
            return $this->accessToken;
        }

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw MissingCredentials::create();
        }

        $this->requestingAccessToken = true;

        try {
            /** @var TokenResponse $response */
            $response = $this->send(
                new TokenRequest($this->clientId, $this->clientSecret)
            );
        } finally {
            $this->requestingAccessToken = false;
        }

        if ($response->failed()) {
            throw AuthenticationFailed::fromResponse($response);
        }

        $accessToken = $response->getAccessToken();

        if (empty($accessToken)) {
            throw AuthenticationFailed::create();
        }

        $this->setAccessToken($accessToken, $response->getExpiresIn());

        return $accessToken;
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return ApiException::fromResponse($response, $senderException);
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        if (! $this->throwOnErrors || $this->requestingAccessToken) {
            return;
        }

        $pendingRequest->middleware()->onResponse(
            callable: static fn (Response $response) => $response->throw(),
            name: 'pagueDevThrowOnErrors',
            order: PipeOrder::LAST,
        );
    }

    public function accessTokenHasExpired(): bool
    {
        if ($this->accessTokenExpiresAt === null) {
            return false;
        }

        return time() >= $this->accessTokenExpiresAt;
    }

    protected function defaultAuth(): ?Authenticator
    {
        if ($this->requestingAccessToken) {
            return null;
        }

        return new TokenAuthenticator($this->getAccessToken());
    }

    protected function defaultConfig(): array
    {
        return [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::VERIFY => true,
        ];
    }

    protected function defaultHeaders(): array
    {
        $headers = empty($this->extraHeaders)
            ? []
            : $this->extraHeaders;

        $headers = array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => empty($this->userAgent)
                ? $this->getDefaultUserAgent()
                : $this->userAgent,
        ]);

        if (! empty($this->subAccount)) {
            $headers[self::SUB_ACCOUNT_HEADER] = $this->subAccount;
        }

        return $headers;
    }

    private function guardBaseUrl(): void
    {
        if ($this->baseUrl === null || $this->baseUrl === '') {
            return;
        }

        $scheme = strtolower((string) parse_url($this->baseUrl, PHP_URL_SCHEME));

        if ($scheme !== 'https') {
            throw InvalidBaseUrl::create();
        }
    }

    private function setAccessToken(string $accessToken, ?int $expiresIn): void
    {
        $this->accessToken = $accessToken;

        $this->accessTokenExpiresAt = $expiresIn === null
            ? null
            : time() + max(0, $expiresIn - self::TOKEN_EXPIRATION_LEEWAY_IN_SECONDS);
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
