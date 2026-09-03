<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Exceptions;

use RuntimeException;
use Saloon\Http\Response;
use Throwable;

class ApiException extends RuntimeException
{
    public const int MAXIMUM_RAW_MESSAGE_LENGTH = 500;

    public const string SUB_ACCOUNT_NOT_FOUND = 'SUB_ACCOUNT_NOT_FOUND';

    public const string SUB_ACCOUNT_FORBIDDEN = 'SUB_ACCOUNT_FORBIDDEN';

    public const string SUB_ACCOUNT_SUSPENDED = 'SUB_ACCOUNT_SUSPENDED';

    public const string SUB_ACCOUNT_REFERENCE_TAKEN = 'SUB_ACCOUNT_REFERENCE_TAKEN';

    public const string SUB_ACCOUNT_INVALID_REFERENCE = 'SUB_ACCOUNT_INVALID_REFERENCE';

    public const string SUB_ACCOUNT_QUOTA_EXCEEDED = 'SUB_ACCOUNT_QUOTA_EXCEEDED';

    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $error = null,
        public readonly array $details = [],
        public readonly ?string $timestamp = null,
        public readonly ?string $traceId = null,
        public readonly ?Response $response = null,
        ?Throwable $previous = null,
        protected readonly array $messages = [],
        protected readonly array $body = [],
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function toArray(): array
    {
        return $this->body;
    }

    public function __debugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'statusCode' => $this->statusCode,
            'error' => $this->error,
            'details' => $this->details,
            'timestamp' => $this->timestamp,
            'traceId' => $this->traceId,
        ];
    }

    public function __serialize(): array
    {
        return $this->__debugInfo();
    }

    public static function fromResponse(
        Response $response,
        ?Throwable $previous = null,
    ): self {
        $body = self::decodeBody($response);

        $status = $response->status();

        $exceptionClass = match (true) {
            $status === 400 => BadRequest::class,
            $status === 401 => Unauthorized::class,
            $status === 403 => Forbidden::class,
            $status === 404 => NotFound::class,
            $status === 409 => Conflict::class,
            $status === 422 => UnprocessableEntity::class,
            $status === 429 => TooManyRequests::class,
            $status >= 500 => ServerError::class,
            default => self::class,
        };

        $messages = self::resolveMessages($body, $response);

        return new $exceptionClass(
            message: self::firstMessage($messages),
            statusCode: $status,
            error: is_string($body['error'] ?? null) ? $body['error'] : null,
            details: is_array($body['details'] ?? null) ? $body['details'] : [],
            timestamp: is_string($body['timestamp'] ?? null) ? $body['timestamp'] : null,
            traceId: is_string($body['traceId'] ?? null) ? $body['traceId'] : null,
            response: $response,
            previous: $previous,
            messages: $messages,
            body: $body,
        );
    }

    protected static function firstMessage(array $messages): string
    {
        $message = $messages[0] ?? '';

        return mb_substr($message, 0, self::MAXIMUM_RAW_MESSAGE_LENGTH);
    }

    public function getErrorCode(): ?string
    {
        $code = $this->details['code'] ?? null;

        return is_string($code) ? $code : $this->error;
    }

    public function hasErrorCode(string $errorCode): bool
    {
        return $this->getErrorCode() === $errorCode;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function decodeBody(Response $response): array
    {
        $body = json_decode($response->body(), true);

        return is_array($body) ? $body : [];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    protected static function resolveMessages(array $body, Response $response): array
    {
        $message = $body['message'] ?? null;

        if (is_string($message) && $message !== '') {
            return [$message];
        }

        if (is_array($message)) {
            $messages = array_values(array_filter($message, 'is_string'));

            if ($messages !== []) {
                return $messages;
            }
        }

        if (is_string($body['error'] ?? null) && $body['error'] !== '') {
            return [$body['error']];
        }

        $rawBody = trim($response->body());

        return $rawBody === '' ? [] : [$rawBody];
    }
}
