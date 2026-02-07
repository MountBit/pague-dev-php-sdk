<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Dtos;

readonly class WebhookEvent
{
    public function __construct(
        public string $event,
        public string $eventId,
        public string $timestamp,
        public array $data,
    ) {}
}
