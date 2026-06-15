<?php

declare(strict_types=1);

use Mindtwo\Monitoring\Contracts\Collector;
use Mindtwo\Monitoring\Contracts\Transport;
use Mindtwo\Monitoring\Data\CollectionResult;
use Mindtwo\Monitoring\Data\Snapshot;
use Mindtwo\Monitoring\Data\TransportResult;
use Mindtwo\Monitoring\Monitor;
use Mindtwo\Monitoring\Server\Cli;
use Mindtwo\Monitoring\SnapshotBuilder;
use Mindtwo\Monitoring\SnapshotFactory;

function cliMonitor(?Transport $transport = null): Monitor
{
    $collector = new class implements Collector
    {
        public function key(): string
        {
            return 'php';
        }

        public function supported(): bool
        {
            return true;
        }

        public function collect(): CollectionResult
        {
            return CollectionResult::ok('php', ['technology' => 'php', 'version' => PHP_VERSION]);
        }
    };

    return (new Monitor(new SnapshotBuilder(new SnapshotFactory), $transport))->register($collector);
}

/**
 * @return array{0: int, 1: string, 2: string} [exit code, stdout, stderr]
 */
function runCli(Monitor $monitor, array $argv): array
{
    $stdout = fopen('php://memory', 'r+');
    $stderr = fopen('php://memory', 'r+');

    $exitCode = (new Cli($monitor, $stdout, $stderr))->run($argv);

    rewind($stdout);
    rewind($stderr);

    return [$exitCode, (string) stream_get_contents($stdout), (string) stream_get_contents($stderr)];
}

test('snapshot prints pretty JSON', function () {
    [$exitCode, $stdout] = runCli(cliMonitor(), ['monitor', 'snapshot']);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('"schema_version": "1.0"')
        ->and(json_decode($stdout, true))->toBeArray();
});

test('snapshot --compact prints compact JSON', function () {
    [$exitCode, $stdout] = runCli(cliMonitor(), ['monitor', 'snapshot', '--compact']);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('"schema_version":"1.0"');
});

test('push --dry-run prints instead of sending', function () {
    [$exitCode, $stdout] = runCli(cliMonitor(), ['monitor', 'push', '--dry-run']);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('"schema_version": "1.0"');
});

test('push reports delivery success', function () {
    $transport = new class implements Transport
    {
        public function send(Snapshot $snapshot): TransportResult
        {
            return TransportResult::delivered(200);
        }
    };

    [$exitCode, $stdout] = runCli(cliMonitor($transport), ['monitor', 'push']);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('delivered (HTTP 200)');
});

test('push fails loudly on delivery errors', function () {
    $transport = new class implements Transport
    {
        public function send(Snapshot $snapshot): TransportResult
        {
            return TransportResult::failed('connection refused');
        }
    };

    [$exitCode, , $stderr] = runCli(cliMonitor($transport), ['monitor', 'push']);

    expect($exitCode)->toBe(1)
        ->and($stderr)->toContain('connection refused');
});

test('show renders the metric table', function () {
    [$exitCode, $stdout] = runCli(cliMonitor(), ['monitor', 'show']);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('METRIC')
        ->and($stdout)->toContain('php')
        ->and($stdout)->toContain('Collected at');
});

test('collectors lists registrations with support state', function () {
    [$exitCode, $stdout] = runCli(cliMonitor(), ['monitor', 'collectors']);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('php')
        ->and($stdout)->toContain('yes');
});

test('unknown commands fail with usage on stderr', function () {
    [$exitCode, , $stderr] = runCli(cliMonitor(), ['monitor', 'frobnicate']);

    expect($exitCode)->toBe(1)
        ->and($stderr)->toContain('Unknown command "frobnicate"')
        ->and($stderr)->toContain('Usage:');
});

test('help prints usage on stdout', function () {
    [$exitCode, $stdout] = runCli(cliMonitor(), ['monitor', 'help']);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('Usage:');
});
