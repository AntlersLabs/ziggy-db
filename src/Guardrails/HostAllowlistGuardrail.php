<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Guardrails;

use AntlersLabs\ZiggyDb\Contracts\Guardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

final class HostAllowlistGuardrail implements Guardrail
{
    public function inspect(ConnectionDetails $source): GuardrailResult
    {
        $allowed = config('ziggy-db.guardrails.allowed_hosts', []);
        $allowed = is_array($allowed) ? $allowed : [];

        if ($allowed === []) {
            return GuardrailResult::pass('host-allowlist', 'No host allowlist is configured.');
        }

        $host = $source->host;

        if ($host !== null && in_array($host, $allowed, true)) {
            return GuardrailResult::pass('host-allowlist', "Host [{$host}] is allowed.");
        }

        return GuardrailResult::fail('host-allowlist', 'Source host ['.($host ?? 'none').'] is not in the allowlist.');
    }
}
