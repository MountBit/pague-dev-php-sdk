<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests\Integration;

use MountBit\PagueDev\Api;
use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected Api $api;

    protected ?string $projectId = null;

    protected ?string $transactionId = null;

    protected ?string $subAccount = null;

    protected function setUp(): void
    {
        parent::setUp();

        $clientId = getenv('PAGUEDEV_CLIENT_ID');

        $clientSecret = getenv('PAGUEDEV_CLIENT_SECRET');

        if (! $clientId || ! $clientSecret) {
            $this->markTestSkipped(
                'PAGUEDEV_CLIENT_ID / PAGUEDEV_CLIENT_SECRET not set.'
            );
        }

        $this->projectId = getenv('PAGUEDEV_PROJECT_ID') ?: null;

        $this->transactionId = getenv('PAGUEDEV_TRANSACTION_ID') ?: null;

        $this->subAccount = getenv('PAGUEDEV_SUB_ACCOUNT') ?: null;

        $this->api = new Api(
            clientId: $clientId,
            clientSecret: $clientSecret,
            baseUrl: getenv('PAGUEDEV_BASE_URL') ?: null,
        );
    }

    protected function uniqueReference(string $prefix): string
    {
        return $prefix.'_'.uniqid(more_entropy: true);
    }
}
