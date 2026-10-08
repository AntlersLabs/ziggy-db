<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Console\Commands;

use AntlersLabs\ZiggyDb\Contracts\Guardrail;
use AntlersLabs\ZiggyDb\Guardrails\DriverGuardrail;
use AntlersLabs\ZiggyDb\Guardrails\EnvironmentGuardrail;
use AntlersLabs\ZiggyDb\Guardrails\GuardrailResult;
use AntlersLabs\ZiggyDb\Guardrails\HostAllowlistGuardrail;
use AntlersLabs\ZiggyDb\Guardrails\ReadOnlyGuardrail;
use AntlersLabs\ZiggyDb\Guardrails\SecureTransportGuardrail;
use AntlersLabs\ZiggyDb\Support\ConnectionDetails;
use Illuminate\Console\Command;
use InvalidArgumentException;

class VerifyCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'ziggy-db:verify {--connection= : The source connection name to verify}';

    /**
     * The command description.
     */
    protected $description = 'Verify that every Ziggy DB guardrail allows pulling from the configured source.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('connection');
        $name = is_string($name) && $name !== '' ? $name : config('ziggy-db.source');
        $name = is_string($name) && $name !== '' ? $name : null;

        if ($name === null) {
            $this->components->error('No source connection is configured. Set [ziggy-db.source] or pass [--connection].');

            return self::FAILURE;
        }

        try {
            $source = ConnectionDetails::fromConnection($name);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $results = array_map(
            static fn (Guardrail $guardrail): GuardrailResult => $guardrail->inspect($source),
            $this->guardrails(),
        );

        $this->table(
            ['Guardrail', 'Result', 'Details'],
            array_map(
                static fn (GuardrailResult $result): array => [
                    $result->guardrail,
                    $result->passed ? 'passed' : 'failed',
                    $result->message,
                ],
                $results,
            ),
        );

        $failures = array_filter($results, static fn (GuardrailResult $result): bool => ! $result->passed);

        if ($failures !== []) {
            $this->components->error(count($failures).' guardrail(s) failed. The source is not safe to pull from.');

            return self::FAILURE;
        }

        $this->components->info('All guardrails passed. The source is safe to pull from.');

        return self::SUCCESS;
    }

    /**
     * The guardrails applied to a source connection.
     *
     * @return list<Guardrail>
     */
    protected function guardrails(): array
    {
        return [
            new EnvironmentGuardrail,
            new DriverGuardrail,
            new HostAllowlistGuardrail,
            new SecureTransportGuardrail,
            new ReadOnlyGuardrail,
        ];
    }
}
