<?php

declare(strict_types=1);

use AntlersLabs\ZiggyDb\ZiggyDb;
use Illuminate\Support\Facades\Artisan;

it('resolves the singleton', function () {
    expect(app(ZiggyDb::class))->toBeInstanceOf(ZiggyDb::class);
});

it('returns the same instance from the container', function () {
    expect(app(ZiggyDb::class))->toBe(app(ZiggyDb::class));
});

it('merges the package config', function () {
    expect(config('ziggy-db.tables.skip'))->toContain('sessions')
        ->and(config('ziggy-db.guardrails.require_read_only'))->toBeTrue()
        ->and(config('ziggy-db.guardrails.require_secure_transport'))->toBeTrue();
});

it('registers the package commands', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('ziggy-db:doctor')
        ->and($commands)->toHaveKey('ziggy-db:verify');
});
