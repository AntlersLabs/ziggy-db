<?php

declare(strict_types=1);

use AntlersLabs\ZiggyDb\Guardrails\DriverGuardrail;
use AntlersLabs\ZiggyDb\Guardrails\EnvironmentGuardrail;
use AntlersLabs\ZiggyDb\Guardrails\HostAllowlistGuardrail;
use AntlersLabs\ZiggyDb\Guardrails\SecureTransportGuardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

function guardrailSource(string $driver, array $config = []): ConnectionDetails
{
    config()->set('database.connections.ziggy_guard', array_merge([
        'driver' => $driver,
        'database' => ':memory:',
    ], $config));

    return ConnectionDetails::fromConnection('ziggy_guard');
}

it('passes the environment guardrail inside allowed environments', function () {
    $result = (new EnvironmentGuardrail)->inspect(guardrailSource('sqlite'));

    expect($result->passed)->toBeTrue();
});

it('fails the environment guardrail outside allowed environments', function () {
    config()->set('ziggy-db.guardrails.allowed_environments', ['production']);

    $result = (new EnvironmentGuardrail)->inspect(guardrailSource('sqlite'));

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toContain('is not allowed');
});

it('passes the driver guardrail for allowed drivers', function () {
    $result = (new DriverGuardrail)->inspect(guardrailSource('pgsql'));

    expect($result->passed)->toBeTrue();
});

it('fails the driver guardrail for disallowed drivers', function () {
    config()->set('ziggy-db.guardrails.allowed_drivers', ['mysql']);

    $result = (new DriverGuardrail)->inspect(guardrailSource('pgsql'));

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toContain('not allowed');
});

it('skips the host allowlist when it is empty', function () {
    $result = (new HostAllowlistGuardrail)->inspect(guardrailSource('mysql', ['host' => 'evil.example']));

    expect($result->passed)->toBeTrue();
});

it('enforces a configured host allowlist', function () {
    config()->set('ziggy-db.guardrails.allowed_hosts', ['db.internal']);

    $allowed = (new HostAllowlistGuardrail)->inspect(guardrailSource('mysql', ['host' => 'db.internal']));
    $blocked = (new HostAllowlistGuardrail)->inspect(guardrailSource('mysql', ['host' => 'evil.example']));

    expect($allowed->passed)->toBeTrue()
        ->and($blocked->passed)->toBeFalse()
        ->and($blocked->message)->toContain('not in the allowlist');
});

it('requires sslmode for remote postgres connections', function () {
    $blocked = (new SecureTransportGuardrail)->inspect(guardrailSource('pgsql', ['host' => 'db.internal']));
    $allowed = (new SecureTransportGuardrail)->inspect(guardrailSource('pgsql', ['host' => 'db.internal', 'sslmode' => 'require']));

    expect($blocked->passed)->toBeFalse()
        ->and($allowed->passed)->toBeTrue();
});

it('requires ssl options for remote mysql connections', function () {
    $blocked = (new SecureTransportGuardrail)->inspect(guardrailSource('mysql', ['host' => 'db.internal']));
    $allowed = (new SecureTransportGuardrail)->inspect(guardrailSource('mysql', ['host' => 'db.internal', 'options' => [1009 => '/etc/ssl/ca.pem']]));

    expect($blocked->passed)->toBeFalse()
        ->and($allowed->passed)->toBeTrue();
});

it('allows local connections without encryption', function () {
    $result = (new SecureTransportGuardrail)->inspect(guardrailSource('mysql', ['host' => '127.0.0.1']));

    expect($result->passed)->toBeTrue();
});
