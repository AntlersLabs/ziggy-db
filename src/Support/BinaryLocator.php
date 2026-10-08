<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Support;

use Illuminate\Support\Facades\Process;
use Throwable;

final class BinaryLocator
{
    public static function find(string $binary): ?string
    {
        $command = PHP_OS_FAMILY === 'Windows'
            ? ['where', $binary]
            : ['sh', '-c', 'command -v -- '.escapeshellarg($binary)];

        try {
            $result = Process::run($command);
        } catch (Throwable) {
            return null;
        }

        if (! $result->successful()) {
            return null;
        }

        $first = strtok(trim($result->output()), "\r\n");

        return $first === false ? null : $first;
    }
}
