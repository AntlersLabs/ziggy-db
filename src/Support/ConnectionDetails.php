<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Support;

use InvalidArgumentException;

final readonly class ConnectionDetails
{
    /**
     * @param  array<array-key, mixed>  $config
     */
    private function __construct(
        public string $name,
        public string $driver,
        public ?string $host,
        public ?int $port,
        public ?string $database,
        public ?string $username,
        public ?string $password,
        public array $config,
    ) {}

    public static function fromConnection(?string $name): self
    {
        if ($name === null || $name === '') {
            throw new InvalidArgumentException('No source database connection is configured.');
        }

        $config = config("database.connections.{$name}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Database connection [{$name}] is not configured.");
        }

        return new self(
            name: $name,
            driver: strtolower(self::stringOrNull($config['driver'] ?? null) ?? ''),
            host: self::stringOrNull($config['host'] ?? null),
            port: self::intOrNull($config['port'] ?? null),
            database: self::stringOrNull($config['database'] ?? null),
            username: self::stringOrNull($config['username'] ?? null),
            password: self::stringOrNull($config['password'] ?? null),
            config: $config,
        );
    }

    public function isSqlite(): bool
    {
        return $this->driver === 'sqlite';
    }

    public function isLocalHost(): bool
    {
        return $this->host === null || in_array($this->host, ['', 'localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function connectionOptions(): array
    {
        $options = $this->config['options'] ?? null;

        return is_array($options) ? $options : [];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
