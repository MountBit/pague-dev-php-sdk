<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

class WebhookEvent
{
    public function __construct(
        public readonly string $event,
        public readonly string $eventId,
        public readonly string $timestamp,
        public readonly array $data
    ) {}
}
