<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Exceptions;

use InvalidArgumentException;

class InvalidBaseUrl extends InvalidArgumentException
{
    public static function create(string $message = ''): self
    {
        return new self(message: $message);
    }
}
