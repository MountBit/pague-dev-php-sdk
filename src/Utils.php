<?php

declare(strict_types=1);

namespace MountBit\PagueDev;

use MountBit\PagueDev\Dtos\WebhookEvent;
use MountBit\PagueDev\Exceptions\InvalidSignature;
use MountBit\PagueDev\Exceptions\InvalidWebhook;

class Utils
{
    public const WEBHOOK_HASH_ALGORITHM = 'sha256';

    public const WEBHOOK_VALID_EVENTS_TYPES = [
        'payment_completed',
        'refund_completed',
        'withdrawal_completed',
        'withdrawal_failed',
    ];

    private function __construct() {}

    /**
     * @throws InvalidSignature|InvalidWebhook
     */
    public static function parseWebhook(
        string $rawBody,
        string $signatureHeader,
        string $webhookSecret,
        bool $shouldThrow = false,
        bool $shouldValidateEventType = false,
        array $validEventTypes = [],
    ): ?WebhookEvent {
        $expectedSignature = hash_hmac(
            self::WEBHOOK_HASH_ALGORITHM,
            $rawBody,
            $webhookSecret,
        );

        if (! hash_equals($expectedSignature, $signatureHeader)) {
            if ($shouldThrow) {
                throw InvalidSignature::create();
            }

            return null;
        }

        $json = json_decode($rawBody, true);

        if ($json === null) {
            if ($shouldThrow) {
                throw InvalidWebhook::create(
                    'The webhook event body could not be parsed'
                );
            }

            return null;
        }

        if ($shouldValidateEventType) {
            $validEventTypes = empty($validEventTypes)
                ? self::WEBHOOK_VALID_EVENTS_TYPES
                : $validEventTypes;

            $eventTypeIsValid = in_array($json['event'], $validEventTypes, true);

            if (! $eventTypeIsValid) {
                if ($shouldThrow) {
                    throw InvalidWebhook::create(
                        'Invalid webhook event: '.$json['event']
                    );
                }

                return null;
            }
        }

        return new WebhookEvent(
            event: $json['event'],
            eventId: $json['eventId'],
            timestamp: $json['timestamp'],
            data: $json['data'],
        );
    }
}
