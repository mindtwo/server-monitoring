<?php

declare(strict_types=1);

namespace Mindtwo\Monitoring\Server;

use Mindtwo\Monitoring\Contracts\ConfigurationRepository;
use Mindtwo\Monitoring\Data\Credentials;
use Mindtwo\Monitoring\Transport\HttpTransport;

/**
 * Environment-driven configuration (.env file with real environment variables
 * taking precedence), falling back to secure defaults.
 */
final class ServerConfigurationRepository implements ConfigurationRepository
{
    /** @var array<string, string> config key => environment variable */
    private const ENVIRONMENT_NAMES = [
        'enabled' => 'MONITORING_ENABLED',
        'project_key' => 'MONITORING_PROJECT_KEY',
        'secret' => 'MONITORING_SECRET',
        'endpoint' => 'MONITORING_ENDPOINT',
        'environment' => 'MONITORING_ENVIRONMENT',
        'ip_allow_list' => 'MONITORING_IP_ALLOW_LIST',
        'route_enabled' => 'MONITORING_ROUTE_ENABLED',
        'signature_tolerance' => 'MONITORING_SIGNATURE_TOLERANCE',
        'cache_seconds' => 'MONITORING_ROUTE_CACHE',
        'timeout' => 'MONITORING_TIMEOUT',
        'project_root' => 'MONITORING_PROJECT_ROOT',
    ];

    /** @var array<string, mixed> */
    private const DEFAULTS = [
        'enabled' => true,
        'environment' => 'production',
        'route_enabled' => true,
        'signature_tolerance' => 300,
        'cache_seconds' => 0,
        'timeout' => 15,
    ];

    public function __construct(private Env $env) {}

    public function credentials(): Credentials
    {
        return new Credentials(
            trim((string) ($this->env->get('MONITORING_PROJECT_KEY') ?? '')),
            trim((string) ($this->env->get('MONITORING_SECRET') ?? ''))
        );
    }

    public function endpoint(): string
    {
        $endpoint = trim((string) ($this->env->get('MONITORING_ENDPOINT') ?? ''));

        return $endpoint !== '' ? $endpoint : HttpTransport::DEFAULT_ENDPOINT;
    }

    /**
     * @return array<int, string>
     */
    public function ipAllowList(): array
    {
        $list = (string) ($this->env->get('MONITORING_IP_ALLOW_LIST') ?? '');

        $entries = [];

        foreach (explode(',', $list) as $entry) {
            if (trim($entry) !== '') {
                $entries[] = trim($entry);
            }
        }

        return $entries;
    }

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $environmentName = self::ENVIRONMENT_NAMES[$key] ?? 'MONITORING_'.strtoupper($key);
        $value = $this->env->get($environmentName);

        if ($value !== null && $value !== '') {
            return $value;
        }

        return $default ?? self::DEFAULTS[$key] ?? null;
    }

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }

    public function routeEnabled(): bool
    {
        return $this->enabled() && $this->boolean('route_enabled');
    }

    public function integer(string $key): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : (int) (self::DEFAULTS[$key] ?? 0);
    }

    private function boolean(string $key): bool
    {
        $value = $this->get($key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return ! in_array(strtolower($value), ['0', 'false', 'off', 'no', ''], true);
        }

        return (bool) $value;
    }
}
