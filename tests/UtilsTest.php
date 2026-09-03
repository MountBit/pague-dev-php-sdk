<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use MountBit\PagueDev\Dtos\WebhookEvent;
use MountBit\PagueDev\Exceptions\InvalidSignature;
use MountBit\PagueDev\Exceptions\InvalidWebhook;
use MountBit\PagueDev\Exceptions\InvalidWebhookEvent;
use MountBit\PagueDev\Exceptions\MalformedWebhookPayload;
use MountBit\PagueDev\Exceptions\MissingWebhookSecret;
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
    public function it_signs_the_payload_with_the_raw_secret_and_the_sha256_prefix(): void
    {
        $rawBody = '{"event":"payment_completed"}';

        $this->assertSame(
            'sha256='.hash_hmac('sha256', $rawBody, $this->secret),
            Utils::signWebhook($rawBody, $this->secret)
        );
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

        $result = Utils::getInstance()->parseWebhook(
            $rawBody,
            $this->sign($rawBody),
            $this->secret
        );

        $this->assertInstanceOf(WebhookEvent::class, $result);
        $this->assertSame('payment_completed', $result->event);
        $this->assertSame('1234', $result->eventId);
        $this->assertSame(['amount' => 100], $result->data);
        $this->assertNull($result->subAccount);
    }

    #[Test]
    public function it_parses_the_sub_account_when_present(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => '1234',
            'timestamp' => '2026-02-05T19:00:00Z',
            'subAccount' => 'loja-centro',
            'data' => [],
        ]);

        $result = Utils::parseWebhook($rawBody, $this->sign($rawBody), $this->secret);

        $this->assertSame('loja-centro', $result->subAccount);
    }

    #[Test]
    public function it_accepts_a_signature_without_the_sha256_prefix(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => '1234',
            'timestamp' => '2026-02-05T19:00:00Z',
            'data' => [],
        ]);

        $signature = hash_hmac('sha256', $rawBody, $this->secret);

        $result = Utils::parseWebhook($rawBody, $signature, $this->secret);

        $this->assertInstanceOf(WebhookEvent::class, $result);
    }

    #[Test]
    public function it_rejects_a_signature_generated_with_the_hashed_secret(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => '1234',
            'timestamp' => '2026-02-05T19:00:00Z',
            'data' => [],
        ]);

        $legacySignature = hash_hmac(
            'sha256',
            $rawBody,
            hash('sha256', $this->secret)
        );

        $this->assertNull(
            Utils::parseWebhook($rawBody, $legacySignature, $this->secret)
        );
    }

    #[Test]
    public function it_rejects_a_signature_from_another_payload(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => '1234',
            'timestamp' => '2026-02-05T19:00:00Z',
            'data' => ['amount' => 100],
        ]);

        $tamperedBody = str_replace('100', '10000', $rawBody);

        $this->assertNull(
            Utils::parseWebhook($tamperedBody, $this->sign($rawBody), $this->secret)
        );
    }

    #[Test]
    public function it_requires_a_webhook_secret(): void
    {
        $this->expectException(MissingWebhookSecret::class);

        Utils::parseWebhook('{}', 'sha256=abc', '');
    }

    #[Test]
    public function it_parses_a_valid_webhook_with_event_type_validation(): void
    {
        $rawBody = json_encode([
            'event' => 'refund_completed',
            'eventId' => 'abcd',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => ['refundId' => 'r1'],
        ]);

        $result = Utils::parseWebhook(
            $rawBody,
            $this->sign($rawBody),
            $this->secret,
            shouldThrow: true,
            shouldValidateEventType: true
        );

        $this->assertSame('refund_completed', $result->event);
    }

    #[Test]
    public function it_accepts_every_documented_event_type(): void
    {
        foreach (Utils::WEBHOOK_VALID_EVENTS_TYPES as $eventType) {
            $rawBody = json_encode([
                'event' => $eventType,
                'eventId' => 'evt_1',
                'timestamp' => '2026-02-05T19:05:00Z',
                'data' => [],
            ]);

            $result = Utils::parseWebhook(
                $rawBody,
                $this->sign($rawBody),
                $this->secret,
                shouldThrow: true,
                shouldValidateEventType: true
            );

            $this->assertSame($eventType, $result->event);
        }
    }

    #[Test]
    public function it_throws_invalid_signature_exception_for_invalid_signature(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'x1',
            'timestamp' => '2026-02-05T19:10:00Z',
            'data' => [],
        ]);

        $this->expectException(InvalidSignature::class);

        Utils::parseWebhook($rawBody, 'invalid_signature', $this->secret, shouldThrow: true);
    }

    #[Test]
    public function it_returns_null_for_invalid_signature(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'x1',
            'timestamp' => '2026-02-05T19:10:00Z',
            'data' => [],
        ]);

        $this->assertNull(
            Utils::parseWebhook($rawBody, 'invalid_signature', $this->secret)
        );
    }

    #[Test]
    public function it_throws_invalid_webhook_exception_for_invalid_json(): void
    {
        $rawBody = '{invalid_json}';

        $this->expectException(InvalidWebhook::class);

        Utils::parseWebhook(
            $rawBody,
            $this->sign($rawBody),
            $this->secret,
            shouldThrow: true
        );
    }

    #[Test]
    public function it_returns_null_for_invalid_json(): void
    {
        $rawBody = '{invalid_json}';

        $this->assertNull(
            Utils::parseWebhook($rawBody, $this->sign($rawBody), $this->secret)
        );
    }

    #[Test]
    public function it_returns_null_for_a_json_payload_that_is_not_an_object(): void
    {
        $rawBody = '"just-a-string"';

        $this->assertNull(
            Utils::parseWebhook($rawBody, $this->sign($rawBody), $this->secret)
        );
    }

    #[Test]
    public function it_throws_when_a_required_field_is_missing(): void
    {
        $rawBody = json_encode(['event' => 'payment_completed']);

        $this->expectException(MalformedWebhookPayload::class);

        Utils::parseWebhook(
            $rawBody,
            $this->sign($rawBody),
            $this->secret,
            shouldThrow: true
        );
    }

    #[Test]
    public function it_defaults_the_data_to_an_empty_array_when_it_is_absent(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'evt_1',
            'timestamp' => '2026-02-05T19:05:00Z',
        ]);

        $result = Utils::parseWebhook($rawBody, $this->sign($rawBody), $this->secret);

        $this->assertSame([], $result->data);
    }

    #[Test]
    public function it_accepts_a_timestamp_inside_the_tolerance(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'evt_1',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => [],
        ]);

        $result = Utils::parseWebhook(
            $rawBody,
            $this->sign($rawBody),
            $this->secret,
            shouldThrow: true,
            timestampHeader: (string) (time() * 1000),
            toleranceInSeconds: 300,
        );

        $this->assertInstanceOf(WebhookEvent::class, $result);
    }

    #[Test]
    public function it_rejects_a_replayed_timestamp(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'evt_1',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => [],
        ]);

        $this->expectException(InvalidWebhook::class);

        Utils::parseWebhook(
            $rawBody,
            $this->sign($rawBody),
            $this->secret,
            shouldThrow: true,
            timestampHeader: (string) ((time() - 3600) * 1000),
            toleranceInSeconds: 300,
        );
    }

    #[Test]
    public function it_rejects_a_missing_timestamp_when_a_tolerance_is_given(): void
    {
        $rawBody = json_encode([
            'event' => 'payment_completed',
            'eventId' => 'evt_1',
            'timestamp' => '2026-02-05T19:05:00Z',
            'data' => [],
        ]);

        $this->assertNull(
            Utils::parseWebhook(
                $rawBody,
                $this->sign($rawBody),
                $this->secret,
                toleranceInSeconds: 300,
            )
        );
    }

    #[Test]
    public function it_throws_invalid_webhook_exception_for_invalid_event_type(): void
    {
        $rawBody = json_encode([
            'event' => 'unknown_event',
            'eventId' => '5678',
            'timestamp' => '2026-02-05T19:15:00Z',
            'data' => [],
        ]);

        $this->expectException(InvalidWebhookEvent::class);

        Utils::parseWebhook(
            $rawBody,
            $this->sign($rawBody),
            $this->secret,
            shouldThrow: true,
            shouldValidateEventType: true
        );
    }

    #[Test]
    public function it_returns_null_for_invalid_event_type_when_should_throw_is_false(): void
    {
        $rawBody = json_encode([
            'event' => 'unknown_event',
            'eventId' => '9999',
            'timestamp' => '2026-02-05T19:20:00Z',
            'data' => [],
        ]);

        $this->assertNull(
            Utils::parseWebhook(
                $rawBody,
                $this->sign($rawBody),
                $this->secret,
                shouldThrow: false,
                shouldValidateEventType: true
            )
        );
    }

    #[Test]
    public function it_allows_custom_valid_event_types(): void
    {
        $rawBody = json_encode([
            'event' => 'custom_event',
            'eventId' => 'evt_456',
            'timestamp' => '2026-02-05T12:30:00.000Z',
            'data' => ['key' => 'value'],
        ]);

        $event = Utils::parseWebhook(
            rawBody: $rawBody,
            signatureHeader: $this->sign($rawBody),
            webhookSecret: $this->secret,
            shouldThrow: true,
            shouldValidateEventType: true,
            validEventTypes: ['custom_event']
        );

        $this->assertSame('custom_event', $event->event);
        $this->assertSame('evt_456', $event->eventId);
    }

    #[Test]
    public function it_generates_an_svg_qr_code(): void
    {
        $qrCode = Utils::getInstance()->generateQrCode(
            data: 'any-string-can-be-encoded',
            outputInterface: QRMarkupSVG::class,
            ecc: EccLevel::M,
        );

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $this->assertStringStartsWith('data:image/svg+xml;base64', $qrCode);
    }

    #[Test]
    public function it_generates_qr_code_with_different_error_correction_levels(): void
    {
        $qrCode = Utils::getInstance()->generateQrCode(
            data: 'qr-with-high-ecc',
            outputInterface: QRMarkupSVG::class,
            ecc: EccLevel::H,
        );

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
    }

    #[Test]
    public function it_applies_the_error_correction_level(): void
    {
        $data = 'pix-copy-and-paste-payload';

        $low = Utils::getInstance()->generateQrCode(data: $data, ecc: EccLevel::L);
        $high = Utils::getInstance()->generateQrCode(data: $data, ecc: EccLevel::H);

        $this->assertNotSame(
            $low,
            $high,
            'The ecc argument must change the generated QR code'
        );
    }

    #[Test]
    public function it_can_generate_qr_code_from_empty_string(): void
    {
        $qrCode = Utils::getInstance()->generateQrCode('');

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
    }

    private function sign(string $body): string
    {
        return Utils::signWebhook($body, $this->secret);
    }
}
