<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Concerns;

use MountBit\PagueDev\Api;

trait HasIdempotencyKey
{
    protected function idempotencyHeaders(?string $idempotencyKey): array
    {
        return empty($idempotencyKey)
            ? []
            : [Api::IDEMPOTENCY_KEY_HEADER => $idempotencyKey];
    }
}
