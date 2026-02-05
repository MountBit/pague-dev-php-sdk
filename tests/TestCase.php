<?php

declare(strict_types=1);

namespace MountBit\PagueDev\Tests;

use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Saloon\Config;
use Saloon\MockConfig;

class TestCase extends FrameworkTestCase
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
}
