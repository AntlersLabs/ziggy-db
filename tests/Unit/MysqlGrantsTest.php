<?php

declare(strict_types=1);

use AntlersLabs\ZiggyDb\Guardrails\MysqlGrants;

it('accepts select-only grants', function () {
    expect(MysqlGrants::violations([
        'GRANT USAGE ON *.* TO `readonly`@`%`',
        'GRANT SELECT, SHOW VIEW ON `app`.* TO `readonly`@`%`',
    ]))->toBe([]);
});

it('reports write privileges', function () {
    expect(MysqlGrants::violations([
        'GRANT SELECT, INSERT, UPDATE, DELETE ON `app`.* TO `reader`@`%`',
    ]))->toBe(['INSERT', 'UPDATE', 'DELETE']);
});

it('reports all privileges grants', function () {
    expect(MysqlGrants::violations([
        'GRANT ALL PRIVILEGES ON *.* TO `admin`@`%`',
    ]))->toBe(['ALL PRIVILEGES']);
});

it('reports unrecognised grants', function () {
    $violations = MysqlGrants::violations(['GRANT `app_reader`@`%` TO `reader`@`%`']);

    expect($violations)->toHaveCount(1)
        ->and($violations[0])->toContain('unrecognised grant');
});
