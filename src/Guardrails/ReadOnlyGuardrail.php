<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Guardrails;

use AntlersLabs\ZiggyDb\Contracts\Guardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

final class ReadOnlyGuardrail implements Guardrail
{
    public function inspect(ConnectionDetails $source): GuardrailResult
    {
        if (config('ziggy-db.guardrails.require_read_only', true) === false) {
            return GuardrailResult::pass('read-only', 'The read-only check is disabled.');
        }

        return match ($source->driver) {
            'mysql', 'mariadb' => $this->inspectMysql($source),
            'pgsql' => $this->inspectPostgres($source),
            'sqlite' => $this->inspectSqlite($source),
            default => GuardrailResult::fail('read-only', "Driver [{$source->driver}] has no read-only check."),
        };
    }

    private function inspectSqlite(ConnectionDetails $source): GuardrailResult
    {
        try {
            $pdo = $this->pdo($source);
            $pdo->exec('PRAGMA query_only = ON');
        } catch (Throwable $exception) {
            return GuardrailResult::fail('read-only', 'Could not lock the SQLite source: '.$exception->getMessage());
        }

        return GuardrailResult::pass('read-only', 'The SQLite source is locked with PRAGMA query_only.');
    }

    private function inspectMysql(ConnectionDetails $source): GuardrailResult
    {
        try {
            $pdo = $this->pdo($source);
            $pdo->exec('SET SESSION TRANSACTION READ ONLY');

            $readOnly = $pdo->query('SELECT @@session.transaction_read_only');

            if ($readOnly === false || (int) $readOnly->fetchColumn() !== 1) {
                return GuardrailResult::fail('read-only', 'The read-only session could not be enforced on the MySQL source.');
            }

            $grants = $pdo->query('SHOW GRANTS FOR CURRENT_USER()');

            if ($grants === false) {
                return GuardrailResult::fail('read-only', 'The MySQL source did not expose its privileges.');
            }

            $rows = [];

            foreach ($grants->fetchAll(PDO::FETCH_COLUMN) as $row) {
                if (is_string($row)) {
                    $rows[] = $row;
                }
            }

            $violations = MysqlGrants::violations($rows);
        } catch (Throwable $exception) {
            return GuardrailResult::fail('read-only', 'Could not verify the MySQL source: '.$exception->getMessage());
        }

        if ($violations !== []) {
            return GuardrailResult::fail('read-only', 'The MySQL user holds write privileges: '.implode(', ', $violations).'.');
        }

        return GuardrailResult::pass('read-only', 'The MySQL session is read-only and the user only holds read privileges.');
    }

    private function inspectPostgres(ConnectionDetails $source): GuardrailResult
    {
        try {
            $pdo = $this->pdo($source);
            $pdo->exec('SET default_transaction_read_only = on');
        } catch (Throwable $exception) {
            return GuardrailResult::fail('read-only', 'Could not prepare a read-only Postgres session: '.$exception->getMessage());
        }

        $probe = 'ziggy_db_probe_'.bin2hex(random_bytes(4));

        try {
            $pdo->beginTransaction();
            $pdo->exec("CREATE TABLE {$probe} (id integer)");
            $pdo->rollBack();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return GuardrailResult::pass('read-only', 'The Postgres source rejected a write probe: '.$this->firstLine($exception->getMessage()));
        }

        return GuardrailResult::fail('read-only', 'The Postgres source accepted a write probe, so the credentials are writable.');
    }

    private function pdo(ConnectionDetails $source): PDO
    {
        return DB::connection($source->name)->getPdo();
    }

    private function firstLine(string $message): string
    {
        $line = strtok($message, "\r\n");

        return $line === false ? $message : $line;
    }
}
