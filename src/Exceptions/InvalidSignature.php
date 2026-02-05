<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Exceptions;

use RuntimeException;

class InvalidSignature extends RuntimeException
{
    public static function create()
    {
        return new self(message: 'Invalid webhook signature');
    }
}
