<?php

declare(strict_types=1);

it('lists the required binaries', function () {
    $this->artisan('ziggy-db:doctor')
        ->expectsOutputToContain('mysqldump')
        ->expectsOutputToContain('pg_dump')
        ->expectsOutputToContain('sqlite3')
        ->assertSuccessful();
});
