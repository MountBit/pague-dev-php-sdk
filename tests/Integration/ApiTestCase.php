<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration;

use MountBit\PagueDev\Api;
use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected Api $api;

    protected ?string $chargeId = null;

    protected ?string $projectId = null;

    protected ?string $transactionId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('PAGUEDEV_SANDBOX_API_KEY');

        if (! $apiKey) {
            $this->markTestSkipped('PAGUEDEV_SANDBOX_API_KEY not set.');
        }

        $this->chargeId = getenv('PAGUEDEV_SANDBOX_CHARGE_ID') ?? null;

        $this->projectId = getenv('PAGUEDEV_SANDBOX_PROJECT_ID') ?? null;

        $this->transactionId = getenv('PAGUEDEV_SANDBOX_TRANSACTION_ID') ?? null;

        $this->api = new Api($apiKey);
    }
}
