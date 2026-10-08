<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Guardrails;

use AntlersLabs\ZiggyDb\Contracts\Guardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

final class SecureTransportGuardrail implements Guardrail
{
    /**
     * PDO MySQL SSL attribute identifiers.
     *
     * @var list<int>
     */
    private const array MYSQL_SSL_OPTIONS = [1009, 1010, 1011, 1012, 1013, 1014];

    public function inspect(ConnectionDetails $source): GuardrailResult
    {
        if (config('ziggy-db.guardrails.require_secure_transport', true) === false) {
            return GuardrailResult::pass('secure-transport', 'Secure transport is not required.');
        }

        if ($source->isSqlite() || $source->isLocalHost()) {
            return GuardrailResult::pass('secure-transport', 'The source connection is local.');
        }

        return match ($source->driver) {
            'mysql', 'mariadb' => $this->inspectMysql($source),
            'pgsql' => $this->inspectPostgres($source),
            default => GuardrailResult::fail('secure-transport', "Driver [{$source->driver}] has no secure transport check."),
        };
    }

    private function inspectMysql(ConnectionDetails $source): GuardrailResult
    {
        foreach (array_keys($source->connectionOptions()) as $key) {
            if (is_int($key) && in_array($key, self::MYSQL_SSL_OPTIONS, true)) {
                return GuardrailResult::pass('secure-transport', 'MySQL SSL options are configured.');
            }
        }

        return GuardrailResult::fail('secure-transport', 'The remote MySQL connection does not configure SSL options.');
    }

    private function inspectPostgres(ConnectionDetails $source): GuardrailResult
    {
        $sslmode = $source->config['sslmode'] ?? null;
        $sslmode = is_string($sslmode) ? strtolower($sslmode) : null;

        if (in_array($sslmode, ['require', 'verify-ca', 'verify-full'], true)) {
            return GuardrailResult::pass('secure-transport', "Postgres sslmode [{$sslmode}] is secure.");
        }

        return GuardrailResult::fail('secure-transport', 'The remote Postgres connection must set sslmode to require, verify-ca, or verify-full.');
    }
}
