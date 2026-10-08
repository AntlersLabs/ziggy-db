<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Console\Commands;

use AntlersLabs\ZiggyDb\Support\BinaryLocator;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'ziggy-db:doctor';

    /**
     * The command description.
     */
    protected $description = 'Check the local dump and import binaries Ziggy DB relies on.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rows = [];

        foreach ($this->binaries() as $binary) {
            $path = BinaryLocator::find($binary);

            $rows[] = [$binary, $path === null ? 'missing' : 'available', $path ?? '-'];
        }

        $this->table(['Binary', 'Status', 'Path'], $rows);

        return self::SUCCESS;
    }

    /**
     * The binaries required for each supported driver.
     *
     * @return list<string>
     */
    protected function binaries(): array
    {
        return ['mysqldump', 'mysql', 'pg_dump', 'psql', 'sqlite3', 'ssh'];
    }
}
