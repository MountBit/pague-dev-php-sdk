<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use MountBit\PagueDev\Api;
use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Saloon\Config;
use Saloon\Http\Faking\MockClient;
use Saloon\MockConfig;

abstract class TestCase extends FrameworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::preventStrayRequests();

        MockConfig::throwOnMissingFixtures();
    }

    protected function fixture(string $path)
    {
        return file_get_contents(__DIR__.'/'.'fixtures'.'/'.ltrim($path, '/'));
    }

    protected function connector(
        MockClient $mockClient,
        ?string $subAccount = null,
    ): Api {
        return (new Api(
            subAccount: $subAccount,
            accessToken: 'test-access-token',
        ))->withMockClient($mockClient);
    }
}
