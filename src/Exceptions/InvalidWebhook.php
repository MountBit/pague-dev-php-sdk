<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Exceptions;

use RuntimeException;

class InvalidWebhook extends RuntimeException
{
    public static function create(string $message = 'Invalid webhook')
    {
        return new self(message: $message);
    }
}
