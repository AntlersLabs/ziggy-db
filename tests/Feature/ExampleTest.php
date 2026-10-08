<?php

declare(strict_types=1);

use AntlersLabs\ZiggyDb\ZiggyDb;

it('resolves the singleton', function () {
    expect(app(ZiggyDb::class))->toBeInstanceOf(ZiggyDb::class);
});

it('returns the same instance from the container', function () {
    expect(app(ZiggyDb::class))->toBe(app(ZiggyDb::class));
});

it('merges the package config', function () {
    expect(config('ziggy-db.placeholder'))->toBe('default');
});

it('registers the artisan command', function () {
    $this->artisan('ziggy-db:placeholder')
        ->expectsOutputToContain('ZiggyDb placeholder command executed.')
        ->assertSuccessful();
});
