<?php

declare(strict_types=1);

use AntlersLabs\ZiggyDb\Guardrails\ReadOnlyGuardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

it('fails when no source connection is configured', function () {
    config()->set('ziggy-db.source', null);

    $this->artisan('ziggy-db:verify')
        ->expectsOutputToContain('No source connection is configured')
        ->assertFailed();
});

it('fails when the configured connection does not exist', function () {
    config()->set('ziggy-db.source', 'missing-source');

    $this->artisan('ziggy-db:verify')
        ->expectsOutputToContain('is not configured')
        ->assertFailed();
});

it('fails when the application environment is not allowed', function () {
    config()->set('ziggy-db.source', 'ziggy_source');
    config()->set('ziggy-db.guardrails.allowed_environments', ['production']);
    config()->set('database.connections.ziggy_source', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    $this->artisan('ziggy-db:verify')
        ->expectsOutputToContain('is not allowed')
        ->assertFailed();
});

it('passes for a guarded local source', function () {
    config()->set('ziggy-db.source', 'ziggy_source');
    config()->set('database.connections.ziggy_source', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    $this->artisan('ziggy-db:verify')
        ->expectsOutputToContain('All guardrails passed')
        ->assertSuccessful();
});

it('accepts an explicit connection option', function () {
    config()->set('database.connections.ziggy_other', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    $this->artisan('ziggy-db:verify', ['--connection' => 'ziggy_other'])
        ->expectsOutputToContain('All guardrails passed')
        ->assertSuccessful();
});

it('locks sqlite sources before pulling', function () {
    config()->set('database.connections.ziggy_source', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    $result = (new ReadOnlyGuardrail)->inspect(ConnectionDetails::fromConnection('ziggy_source'));

    expect($result->passed)->toBeTrue()
        ->and($result->message)->toContain('query_only');
});
