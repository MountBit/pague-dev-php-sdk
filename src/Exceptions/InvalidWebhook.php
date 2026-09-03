<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Exceptions;

use RuntimeException;

class InvalidWebhook extends RuntimeException
{
    public static function create(string $message = ''): self
    {
        return new self(message: $message);
    }
}
