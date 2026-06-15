<?php

declare(strict_types=1);

namespace Mindtwo\Monitoring\Server;

use Mindtwo\Monitoring\Collectors\DefaultCollectors;
use Mindtwo\Monitoring\Data\Source;
use Mindtwo\Monitoring\Monitor;
use Mindtwo\Monitoring\Server\Collectors\DockerCollector;
use Mindtwo\Monitoring\Server\Collectors\ServerLoadCollector;
use Mindtwo\Monitoring\SnapshotBuilder;
use Mindtwo\Monitoring\SnapshotFactory;
use Mindtwo\Monitoring\Transport\HmacRequestSigner;
use Mindtwo\Monitoring\Transport\HttpTransport;

/**
 * Assembles a fully wired Monitor for this server: the base collector catalog
 * plus the server-level collectors (Docker, load), with the HTTP push
 * transport from the environment configuration.
 */
final class MonitorFactory
{
    public static function make(?ServerConfigurationRepository $config = null, ?string $projectRoot = null): Monitor
    {
        $config ??= new ServerConfigurationRepository(Env::load(self::projectDir().'/.env'));

        $configuredRoot = trim((string) $config->get('project_root', ''));
        $projectRoot ??= $configuredRoot !== '' && is_dir($configuredRoot) ? $configuredRoot : self::projectDir();

        $projectKey = $config->credentials()->projectKey;

        $factory = new SnapshotFactory(
            Source::plugin(Source::TYPE_SERVER, 'mindtwo/server-monitoring'),
            (string) $config->get('environment', 'production'),
            $projectKey !== '' ? $projectKey : null
        );

        $transport = new HttpTransport(
            $config->endpoint(),
            $config->credentials(),
            new HmacRequestSigner,
            max(1, $config->integer('timeout'))
        );

        $monitor = new Monitor(new SnapshotBuilder($factory), $transport);

        $monitor->replace(...DefaultCollectors::make(projectRoot: $projectRoot));
        $monitor->replace(
            new DockerCollector,
            new ServerLoadCollector,
        );

        return $monitor;
    }

    /**
     * The server-monitoring project directory (where .env lives).
     */
    public static function projectDir(): string
    {
        return dirname(__DIR__);
    }

    private function __construct()
    {
        // Static factory — never instantiated.
    }
}
