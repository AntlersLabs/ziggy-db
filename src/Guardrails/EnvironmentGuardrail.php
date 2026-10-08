<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Guardrails;

use AntlersLabs\ZiggyDb\Contracts\Guardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

final class EnvironmentGuardrail implements Guardrail
{
    public function inspect(ConnectionDetails $source): GuardrailResult
    {
        $allowed = config('ziggy-db.guardrails.allowed_environments', ['local', 'testing']);
        $allowed = is_array($allowed) ? $allowed : [];

        $environment = app()->environment();

        foreach ($allowed as $value) {
            if ($value === $environment) {
                return GuardrailResult::pass('environment', "Application environment [{$environment}] is allowed.");
            }
        }

        return GuardrailResult::fail('environment', "Application environment [{$environment}] is not allowed.");
    }
}
