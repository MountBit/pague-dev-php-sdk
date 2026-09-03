<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Exceptions;

use Saloon\Http\Response;
use Throwable;

class AuthenticationFailed extends ApiException
{
    public static function create(string $message = ''): self
    {
        return new self(message: $message, statusCode: 0);
    }

    public static function fromResponse(
        Response $response,
        ?Throwable $previous = null,
    ): self {
        $body = self::decodeBody($response);

        $messages = self::resolveMessages($body, $response);

        return new self(
            message: self::firstMessage($messages),
            statusCode: $response->status(),
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
}
