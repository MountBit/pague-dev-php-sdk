<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use MountBit\PagueDev\Dtos\WebhookEvent;
use MountBit\PagueDev\Exceptions\InvalidSignature;
use MountBit\PagueDev\Exceptions\InvalidWebhook;
use MountBit\PagueDev\Utils;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    private string $secret;

    protected function setUp(): void
    {
        $this->secret = 'my_secret_key';
    }

    #[Test]
    public function it_parses_a_valid_webhook_without_event_type_validation(): void
    {
        $payload = [
            'event' => 'payment_completed',
            'eventId' => '1234',
            'timestamp' => '2026-02-05T19:00:00Z',
            'data' => ['amount' => 100],
        ];

        $rawBody = json_encode($payload);
        $signature = $this->sign($rawBody);

        $result = Utils::parseWebhook($rawBody, $signature, $this->secret);

        $this->assertInstanceOf(WebhookEvent::class, $result);
        $this->assertSame('payment_completed', $result->event);
        $this->assertSame('1234', $result->eventId);
        $this->assertSame(['amount' => 100], $result->data);
    }

    #[Test]
    public function it_parses_a_valid_webhook_with_event_type_validation(): void
    {
        $payload = [
            'event' => 'refund_completed',
            'eventId' => 'abcd',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => ['refundId' => 'r1'],
        ];

        $rawBody = json_encode($payload);
        $signature = $this->sign($rawBody);

        $result = Utils::parseWebhook(
            $rawBody,
            $signature,
            $this->secret,
            shouldThrow: true,
            shouldValidateEventType: true
        );

        $this->assertInstanceOf(WebhookEvent::class, $result);
        $this->assertSame('refund_completed', $result->event);
    }

    #[Test]
    public function it_throws_invalid_signature_exception_for_invalid_signature(): void
    {
        $payload = [
            'event' => 'payment_completed',
            'eventId' => 'x1',
            'timestamp' => '2026-02-05T19:10:00Z',
            'data' => [],
        ];

        $rawBody = json_encode($payload);
        $invalidSignature = 'invalid_signature';

        $this->expectException(InvalidSignature::class);

        Utils::parseWebhook(
            $rawBody,
            $invalidSignature,
            $this->secret,
            shouldThrow: true
        );
    }

    #[Test]
    public function it_throws_invalid_webhook_exception_for_invalid_json(): void
    {
        $rawBody = '{invalid_json}';
        $signature = $this->sign($rawBody);

        $this->expectException(InvalidWebhook::class);

        Utils::parseWebhook(
            $rawBody,
            $signature,
            $this->secret,
            shouldThrow: true
        );
    }

    #[Test]
    public function it_throws_invalid_webhook_exception_for_invalid_event_type(): void
    {
        $payload = [
            'event' => 'unknown_event',
            'eventId' => '5678',
            'timestamp' => '2026-02-05T19:15:00Z',
            'data' => [],
        ];

        $rawBody = json_encode($payload);
        $signature = $this->sign($rawBody);

        $this->expectException(InvalidWebhook::class);

        Utils::parseWebhook(
            $rawBody,
            $signature,
            $this->secret,
            shouldThrow: true,
            shouldValidateEventType: true
        );
    }

    #[Test]
    public function it_returns_null_for_invalid_event_type_when_should_throw_is_false(): void
    {
        $payload = [
            'event' => 'unknown_event',
            'eventId' => '9999',
            'timestamp' => '2026-02-05T19:20:00Z',
            'data' => [],
        ];

        $rawBody = json_encode($payload);
        $signature = $this->sign($rawBody);

        $result = Utils::parseWebhook(
            $rawBody,
            $signature,
            $this->secret,
            shouldThrow: false,
            shouldValidateEventType: true
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_throws_exception_when_event_type_is_invalid(): void
    {
        $payload = json_encode([
            'event' => 'unknown_event',
            'eventId' => 'evt_123',
            'timestamp' => '2026-02-05T12:00:00.000Z',
            'data' => [],
        ]);
        $signature = $this->sign($payload);

        $this->expectException(InvalidWebhook::class);
        $this->expectExceptionMessage('Invalid webhook event: unknown_event');

        Utils::parseWebhook(
            rawBody: $payload,
            signatureHeader: $signature,
            webhookSecret: $this->secret,
            shouldThrow: true,
            shouldValidateEventType: true
        );
    }

    #[Test]
    public function it_allows_custom_valid_event_types(): void
    {
        $payload = json_encode([
            'event' => 'custom_event',
            'eventId' => 'evt_456',
            'timestamp' => '2026-02-05T12:30:00.000Z',
            'data' => ['key' => 'value'],
        ]);
        $signature = $this->sign($payload);

        $event = Utils::parseWebhook(
            rawBody: $payload,
            signatureHeader: $signature,
            webhookSecret: $this->secret,
            shouldThrow: true,
            shouldValidateEventType: true,
            validEventTypes: ['custom_event']
        );

        $this->assertSame('custom_event', $event->event);
        $this->assertSame('evt_456', $event->eventId);
    }

    private function sign(string $body): string
    {
        return hash_hmac('sha256', $body, $this->secret);
    }
}
