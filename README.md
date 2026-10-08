<div align="center">
    <h1>Ziggy DB</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/antlerslabs/ziggy-db"><img src="https://img.shields.io/packagist/v/antlerslabs/ziggy-db.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/antlerslabs/ziggy-db"><img src="https://img.shields.io/packagist/php-v/antlerslabs/ziggy-db.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/antlerslabs/ziggy-db"><img src="https://badge.laravel.cloud/badge/antlerslabs/ziggy-db?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/antlerslabs/ziggy-db/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/antlerslabs/ziggy-db/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/antlerslabs/ziggy-db"><img src="https://img.shields.io/packagist/dt/antlerslabs/ziggy-db.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Ziggy DB pulls production databases to local development databases through explicit, auditable, read-only guardrails.

The design goal is simple: a production pull should be **boring and safe**. It should fail loudly instead of doing something questionable.

## Status

Implemented:

- `config/ziggy-db.php`
- `ziggy-db:verify`
- `ziggy-db:doctor`

Planned next:

- `ziggy-db:pull`
- CLI dump and import drivers
- Table `only` / `skip` enforcement during transfer
- Staging, anonymization, and promotion
- Pull audit logging

## Requirements

- PHP `^8.3`
- Laravel `^12.0 || ^13.0`
- A dedicated read-only production database user
- For the eventual transfer layer:
  - MySQL/MariaDB: `mysqldump` and `mysql`
  - PostgreSQL: `pg_dump` and `psql`
  - SQLite: `sqlite3`
  - Remote hosts: `ssh`

Run `ziggy-db:doctor` to check the binaries available on the current machine.

## Installation

Install the package through Composer:

```bash
composer require antlerslabs/ziggy-db
```

Publish the configuration:

```bash
php artisan vendor:publish --tag="ziggy-db-config"
```

### Testing against a local copy

Ziggy DB is not published yet. Point a real application at this repository with a Composer path repository:

```json
"repositories": [
    {
        "type": "path",
        "url": "D:/Codes/labs/ziggys",
        "options": {
            "symlink": true
        }
    }
]
```

On Windows, enable Developer Mode so Composer can create the symlink. Otherwise Composer copies the package and the application will not see package edits until `composer update antlerslabs/ziggy-db` runs again.

Then require the development version:

```bash
composer require --dev "antlerslabs/ziggy-db:*@dev"
```

## Configuration

Set the source connection name in the package config or environment:

```dotenv
ZIGGY_DB_SOURCE=mysql_readonly
ZIGGY_DB_TARGET=mysql
```

Define `mysql_readonly` as an ordinary connection in the application's `config/database.php`. Ziggy DB does not invent a separate credential format; it reuses Laravel database connections.

The published config controls:

| Key | Purpose |
| --- | --- |
| `source` | Source connection name. |
| `target` | Target connection name. `null` means the default connection. |
| `tables.only` | When non-empty, import data only for these tables. |
| `tables.skip` | Table data that is never imported. `skip` wins over `only`. |
| `guardrails.allowed_environments` | Application environments where pulls may run. |
| `guardrails.require_read_only` | Abort when source credentials look writable. |
| `guardrails.require_secure_transport` | Refuse unencrypted remote connections. |
| `guardrails.allowed_hosts` | Optional source host allowlist. |
| `guardrails.allowed_drivers` | Drivers Ziggy DB may pull from. |
| `guardrails.require_confirmation` | Require typing the target database name before import. Reserved for the pull command. |
| `anonymize.enabled` | Scrub configured columns before promotion. |
| `anonymize.columns` | `table.column` map, with `*` allowed as the table. |
| `audit.enabled` | Record pulls with source, duration, and outcome. |
| `audit.channel` | Log channel, or `null` for the default channel. |

Schema is always intended to remain complete. `only` and `skip` filter table **data**, not table structure. A skipped table should still exist locally, but stay empty, so the application boots normally.

Default skipped data includes operational tables:

```php
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
```

## Guardrails

Every pull starts from a configured Laravel connection. Verification inspects, in this order:

1. `environment`
2. `driver`
3. `host-allowlist`
4. `secure-transport`
5. `read-only`

### MySQL and MariaDB

The read-only guardrail:

- Enables `SET SESSION TRANSACTION READ ONLY`
- Verifies the session setting took effect
- Reads `SHOW GRANTS FOR CURRENT_USER()`
- Fails when the user holds anything beyond:
  - `SELECT`
  - `USAGE`
  - `SHOW VIEW`
  - `LOCK TABLES`

Unrecognized grants also fail. A reporting or backup role should not have an opaque privilege path to writes.

Remote MySQL connections must configure PDO SSL attributes. Local connections are exempt.

### PostgreSQL

The read-only guardrail:

- Enables `SET default_transaction_read_only = on`
- Attempts to create a randomly named probe table in a transaction
- Rolls the transaction back
- Passes when PostgreSQL rejects the write
- Fails when the credentials accept the write

Remote PostgreSQL connections must use an `sslmode` of `require`, `verify-ca`, or `verify-full`. Local connections are exempt.

### SQLite

SQLite has no database users. The guardrail locks the opened connection with `PRAGMA query_only = ON`. SQLite is useful for package development and tests, but it is not a production access-control boundary.

## Commands

### Verify a source

```bash
php artisan ziggy-db:verify
php artisan ziggy-db:verify --connection=mysql_readonly
```

Exit code is zero only when every guardrail passes. The command prints a table naming each guardrail, its result, and explanatory detail.

### Check local tooling

```bash
php artisan ziggy-db:doctor
```

This reports whether `mysqldump`, `mysql`, `pg_dump`, `psql`, `sqlite3`, and `ssh` are available.

### Planned pull behavior

The future `ziggy-db:pull` command will support:

```bash
php artisan ziggy-db:pull
php artisan ziggy-db:pull --source=mysql_readonly --target=mysql
php artisan ziggy-db:pull --only=users,orders
php artisan ziggy-db:pull --skip=sessions,cache
php artisan ziggy-db:pull --dry-run
php artisan ziggy-db:pull --no-anonymize
php artisan ziggy-db:pull --force
```

Planned behavior:

1. Run every source guardrail.
2. Refuse application environments outside the allowlist.
3. Require explicit confirmation unless `--force` is used in an allowed non-production context.
4. Dump with the matching CLI tool:
   - MySQL/MariaDB: `mysqldump`
   - PostgreSQL: `pg_dump`
   - SQLite: `sqlite3`
5. Import into an isolated staging target.
6. Apply configured anonymization before promotion.
7. Promote staging data to the requested target.
8. Remove staging artifacts.
9. Write one audit record for the pull.

Initial transfers are intentionally same-engine. CLI dumps cannot reliably move PostgreSQL SQL into MySQL without a row-level pipeline, which is a separate future feature.

## Example workflow

1. Create a read-only production MySQL user.
2. Grant it only `SELECT`, plus `SHOW VIEW` if views are needed.
3. Add that connection to the application as `mysql_readonly`.
4. Require TLS or an SSH path to the server.
5. Set `ZIGGY_DB_SOURCE=mysql_readonly`.
6. Publish and review `config/ziggy-db.php`.
7. Run `ziggy-db:doctor`.
8. Run `ziggy-db:verify`.
9. Fix every failing guardrail before attempting a pull.
10. Run `ziggy-db:pull` only in a local environment.

## Development

Full validation:

```bash
composer test
```

Focused checks:

```bash
composer test:unit
composer lint:check
composer analyse
```

Manipulate the Testbench workbench application:

```bash
composer build
composer serve
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Ziggy DB! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [eXeis-ixt](https://github.com/antlerslabs)
- [All Contributors](../../contributors)

## License

Ziggy DB is open-sourced software licensed under the [MIT license](LICENSE.md).
