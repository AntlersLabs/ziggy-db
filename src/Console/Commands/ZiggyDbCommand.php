<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Console\Commands;

use Illuminate\Console\Command;

class ZiggyDbCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'ziggy-db:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package ziggy-db.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('ZiggyDb placeholder command executed.');

        return self::SUCCESS;
    }
}
