<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Source Connection
    |--------------------------------------------------------------------------
    |
    | The database connection from config/database.php that Ziggy DB pulls
    | from. Point this at a read-only replica or a hardened production
    | connection protected by a SELECT-only database user.
    |
    */

    'source' => env('ZIGGY_DB_SOURCE'),

    /*
    |--------------------------------------------------------------------------
    | Target Connection
    |--------------------------------------------------------------------------
    |
    | The local connection that receives the data. When null, the default
    | database connection is used.
    |
    */

    'target' => env('ZIGGY_DB_TARGET'),

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | Table data is filtered with these lists. Schema is always imported in
    | full so the local application still boots. When "only" is non-empty,
    | only those tables have their data imported. "skip" always wins.
    |
    */

    'tables' => [
        'only' => [],
        'skip' => [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'sessions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guardrails
    |--------------------------------------------------------------------------
    */

    'guardrails' => [

        /*
        | Application environments in which pulls may run.
        */

        'allowed_environments' => ['local', 'testing'],

        /*
        | Abort when the source credentials appear to be writable.
        */

        'require_read_only' => true,

        /*
        | Refuse remote connections that are not encrypted.
        */

        'require_secure_transport' => true,

        /*
        | When non-empty, the source host must match one of these values.
        */

        'allowed_hosts' => [],

        /*
        | Database drivers Ziggy DB may pull from.
        */

        'allowed_drivers' => ['mysql', 'mariadb', 'pgsql', 'sqlite'],

        /*
        | Require the operator to type the target database name before importing.
        */

        'require_confirmation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Anonymization
    |--------------------------------------------------------------------------
    |
    | Columns listed here are scrubbed before data is promoted to the target.
    | Keys are "table.column" names (a "*" wildcard for the table is allowed)
    | and values are anonymizer names.
    |
    | 'users.email' => 'email',
    | 'users.name' => 'name',
    | '*.password' => 'redact',
    |
    */

    'anonymize' => [
        'enabled' => false,
        'columns' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log
    |--------------------------------------------------------------------------
    |
    | Every pull is recorded with its source, duration, row counts, and
    | outcome. Set the channel to null to use the default log channel.
    |
    */

    'audit' => [
        'enabled' => true,
        'channel' => null,
    ],

];
