<?php

declare(strict_types=1);

use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

it('builds details from a configured connection', function () {
    config()->set('database.connections.ziggy_pgsql', [
        'driver' => 'pgsql',
        'host' => 'db.internal',
        'port' => '5432',
        'database' => 'production',
        'username' => 'readonly',
        'password' => 'secret',
        'sslmode' => 'require',
    ]);

    $details = ConnectionDetails::fromConnection('ziggy_pgsql');

    expect($details->name)->toBe('ziggy_pgsql')
        ->and($details->driver)->toBe('pgsql')
        ->and($details->host)->toBe('db.internal')
        ->and($details->port)->toBe(5432)
        ->and($details->database)->toBe('production')
        ->and($details->username)->toBe('readonly')
        ->and($details->password)->toBe('secret')
        ->and($details->isSqlite())->toBeFalse()
        ->and($details->isLocalHost())->toBeFalse();
});

it('rejects a missing connection', function () {
    ConnectionDetails::fromConnection('missing');
})->throws(InvalidArgumentException::class);

it('rejects a null connection name', function () {
    ConnectionDetails::fromConnection(null);
})->throws(InvalidArgumentException::class);

it('detects local hosts', function () {
    config()->set('database.connections.ziggy_local', [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'database' => 'app',
    ]);

    expect(ConnectionDetails::fromConnection('ziggy_local')->isLocalHost())->toBeTrue();
});

it('exposes connection options', function () {
    config()->set('database.connections.ziggy_options', [
        'driver' => 'mysql',
        'host' => 'db.internal',
        'database' => 'app',
        'options' => [1009 => '/etc/ssl/ca.pem'],
    ]);

    expect(ConnectionDetails::fromConnection('ziggy_options')->connectionOptions())->toBe([1009 => '/etc/ssl/ca.pem']);
});
