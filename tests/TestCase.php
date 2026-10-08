<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Tests;

use AntlersLabs\ZiggyDb\ZiggyDbServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ZiggyDbServiceProvider::class,
        ];
    }
}
