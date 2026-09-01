<?php

declare(strict_types=1);

namespace MountBit\PagueDev;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use MountBit\PagueDev\Dtos\WebhookEvent;
use MountBit\PagueDev\Exceptions\ExpiredWebhookTimestamp;
use MountBit\PagueDev\Exceptions\InvalidSignature;
use MountBit\PagueDev\Exceptions\InvalidWebhook;
use MountBit\PagueDev\Exceptions\InvalidWebhookEvent;
use MountBit\PagueDev\Exceptions\MalformedWebhookPayload;
use MountBit\PagueDev\Exceptions\MissingWebhookSecret;

class Utils
{
    public const WEBHOOK_HASH_ALGORITHM = 'sha256';

    public const WEBHOOK_SIGNATURE_PREFIX = 'sha256=';

    public const WEBHOOK_EVENT_HEADER = 'X-Pague-Event';

    public const WEBHOOK_ID_HEADER = 'X-Pague-Webhook-ID';

    public const WEBHOOK_SIGNATURE_HEADER = 'X-Pague-Signature';

    public const WEBHOOK_TIMESTAMP_HEADER = 'X-Pague-Timestamp';

    public const WEBHOOK_VALID_EVENTS_TYPES = [
        'payment_completed',
        'payment_expired',
        'refund_completed',
        'withdrawal_completed',
        'withdrawal_failed',
        'withdrawal_reversed',
        'balance_block_created',
        'balance_block_approved',
        'balance_block_rejected',
    ];

    private static ?self $instance = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

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
        ?string $timestampHeader = null,
        ?int $toleranceInSeconds = null,
    ): ?WebhookEvent {
        if ($webhookSecret === '') {
            throw new MissingWebhookSecret;
        }

        if (! self::signatureIsValid($rawBody, $signatureHeader, $webhookSecret)) {
            if ($shouldThrow) {
                throw new InvalidSignature;
            }

            return null;
        }

        if ($toleranceInSeconds !== null) {
            if (! self::timestampIsValid($timestampHeader, $toleranceInSeconds)) {
                if ($shouldThrow) {
                    throw new ExpiredWebhookTimestamp;
                }

                return null;
            }
        }

        $json = json_decode($rawBody, true);

        if (! is_array($json)) {
            if ($shouldThrow) {
                throw new MalformedWebhookPayload;
            }

            return null;
        }

        foreach (['event', 'eventId', 'timestamp'] as $field) {
            if (! is_string($json[$field] ?? null) || $json[$field] === '') {
                if ($shouldThrow) {
                    throw new MalformedWebhookPayload;
                }

                return null;
            }
        }

        if ($shouldValidateEventType) {
            $validEventTypes = empty($validEventTypes)
                ? self::WEBHOOK_VALID_EVENTS_TYPES
                : $validEventTypes;

            $eventTypeIsValid = in_array($json['event'], $validEventTypes, true);

            if (! $eventTypeIsValid) {
                if ($shouldThrow) {
                    throw new InvalidWebhookEvent;
                }

                return null;
            }
        }

        return new WebhookEvent(
            event: $json['event'],
            eventId: $json['eventId'],
            timestamp: $json['timestamp'],
            data: is_array($json['data'] ?? null) ? $json['data'] : [],
            subAccount: is_string($json['subAccount'] ?? null) ? $json['subAccount'] : null,
        );
    }

    public static function signWebhook(string $rawBody, string $webhookSecret): string
    {
        return self::WEBHOOK_SIGNATURE_PREFIX.hash_hmac(
            self::WEBHOOK_HASH_ALGORITHM,
            $rawBody,
            $webhookSecret,
        );
    }

    public function generateQrCode(
        string $data,
        string $outputInterface = QRMarkupSVG::class,
        int $ecc = EccLevel::M,
    ): string {
        $options = new QROptions([
            'eccLevel' => $ecc,
            'outputInterface' => $outputInterface,
            'outputBase64' => true,
        ]);

        return (new QRCode(options: $options))->render($data);
    }

    private static function signatureIsValid(
        string $rawBody,
        string $signatureHeader,
        string $webhookSecret,
    ): bool {
        $expectedSignature = self::signWebhook($rawBody, $webhookSecret);

        $providedSignature = str_starts_with($signatureHeader, self::WEBHOOK_SIGNATURE_PREFIX)
            ? $signatureHeader
            : self::WEBHOOK_SIGNATURE_PREFIX.$signatureHeader;

        return hash_equals($expectedSignature, $providedSignature);
    }

    private static function timestampIsValid(
        ?string $timestampHeader,
        int $toleranceInSeconds,
    ): bool {
        if ($timestampHeader === null || ! ctype_digit($timestampHeader)) {
            return false;
        }

        $timestampInSeconds = (int) ((int) $timestampHeader / 1000);

        return abs(time() - $timestampInSeconds) <= $toleranceInSeconds;
    }
}
