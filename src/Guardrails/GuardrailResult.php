<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Guardrails;

final readonly class GuardrailResult
{
    private function __construct(
        public string $guardrail,
        public bool $passed,
        public string $message,
    ) {}

    public static function pass(string $guardrail, string $message): self
    {
        return new self($guardrail, true, $message);
    }

    public static function fail(string $guardrail, string $message): self
    {
        return new self($guardrail, false, $message);
    }
}
