<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb;

use AntlersLabs\ZiggyDb\Console\Commands\ZiggyDbCommand;
use Illuminate\Support\ServiceProvider;

class ZiggyDbServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ziggy-db.php', 'ziggy-db');

        $this->app->singleton(ZiggyDb::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/ziggy-db.php' => config_path('ziggy-db.php'),
        ], ['ziggy-db', 'ziggy-db-config']);

        $this->commands([
            ZiggyDbCommand::class,
        ]);
    }
}
