<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Contracts;

use AntlersLabs\ZiggyDb\Guardrails\GuardrailResult;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;

interface Guardrail
{
    public function inspect(ConnectionDetails $source): GuardrailResult;
}
