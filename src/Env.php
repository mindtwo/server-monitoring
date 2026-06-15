<?php

declare(strict_types=1);

namespace Mindtwo\Monitoring\Server;

/**
 * Minimal .env loader: KEY=VALUE lines with optional quotes, comments and
 * export prefixes. Real environment variables always win over file values —
 * no dependency on a dotenv library, by design.
 */
final class Env
{
    /** @var array<string, string> */
    private array $values;

    /**
     * @param  array<string, string>  $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public static function load(string $path): self
    {
        if (! is_readable($path)) {
            return new self;
        }

        return new self(self::parse((string) file_get_contents($path)));
    }

    public function get(string $name): ?string
    {
        $real = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if (is_string($real) && $real !== '') {
            return $real;
        }

        return $this->values[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function parse(string $contents): array
    {
        $values = [];

        foreach (preg_split('/\r?\n/', $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            [$name, $value] = explode('=', $line, 2);

            $name = trim($name);

            if ($name === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
                continue;
            }

            $value = trim($value);

            // Strip surrounding quotes; otherwise cut inline comments.
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
                $value = substr($value, 1, -1);
            } elseif (($position = strpos($value, ' #')) !== false) {
                $value = rtrim(substr($value, 0, $position));
            }

            $values[$name] = $value;
        }

        return $values;
    }
}
