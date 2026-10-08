<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Guardrails;

use AntlersLabs\ZiggyDb\Contracts\Guardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

final class DriverGuardrail implements Guardrail
{
    public function inspect(ConnectionDetails $source): GuardrailResult
    {
        if ($source->driver === '') {
            return GuardrailResult::fail('driver', "The connection [{$source->name}] does not define a driver.");
        }

        $allowed = config('ziggy-db.guardrails.allowed_drivers', []);
        $allowed = is_array($allowed) ? $allowed : [];

        if (! in_array($source->driver, $allowed, true)) {
            return GuardrailResult::fail('driver', "Driver [{$source->driver}] is not allowed.");
        }

        return GuardrailResult::pass('driver', "Driver [{$source->driver}] is allowed.");
    }
}
