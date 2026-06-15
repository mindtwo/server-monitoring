<?php

declare(strict_types=1);

namespace Mindtwo\Monitoring\Server;

use Mindtwo\Monitoring\Data\CollectionResult;
use Mindtwo\Monitoring\Monitor;

/**
 * The bin/monitor command line: push, show, snapshot and collectors —
 * dependency-free and fully testable through injected output buffers.
 */
final class Cli
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    /** @var resource */
    private $stdout;

    /** @var resource */
    private $stderr;

    /**
     * @param  resource|null  $stdout
     * @param  resource|null  $stderr
     */
    public function __construct(
        private Monitor $monitor,
        $stdout = null,
        $stderr = null
    ) {
        $this->stdout = $stdout ?? STDOUT;
        $this->stderr = $stderr ?? STDERR;
    }

    /**
     * @param  array<int, string>  $argv
     */
    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $flags = array_slice($argv, 2);

        switch ($command) {
            case 'push':
                return $this->push(in_array('--dry-run', $flags, true));
            case 'snapshot':
                return $this->snapshot(in_array('--compact', $flags, true));
            case 'show':
                return $this->show();
            case 'collectors':
                return $this->collectors();
            case 'help':
            case '--help':
            case '-h':
                $this->usage($this->stdout);

                return self::SUCCESS;
            default:
                $this->writeLine($this->stderr, sprintf('Unknown command "%s".', $command));
                $this->usage($this->stderr);

                return self::FAILURE;
        }
    }

    private function push(bool $dryRun): int
    {
        if ($dryRun) {
            $this->writeLine($this->stdout, $this->monitor->snapshot()->toJson(JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $result = $this->monitor->push();

        if ($result->success) {
            $this->writeLine($this->stdout, sprintf('Monitoring snapshot delivered (HTTP %s).', $result->statusCode ?? 'n/a'));

            return self::SUCCESS;
        }

        $this->writeLine($this->stderr, 'Monitoring snapshot could not be delivered: '.($result->error ?? 'unknown error'));

        return self::FAILURE;
    }

    private function snapshot(bool $compact): int
    {
        $this->writeLine($this->stdout, $this->monitor->snapshot()->toJson($compact ? 0 : JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function show(): int
    {
        $snapshot = $this->monitor->snapshot();

        $this->writeLine($this->stdout, sprintf('%-20s %-12s %s', 'METRIC', 'STATUS', 'DETAILS'));

        foreach ($snapshot->results() as $key => $result) {
            $this->writeLine($this->stdout, sprintf('%-20s %-12s %s', $key, $result->status, $this->summarize($result)));
        }

        $this->writeLine($this->stdout, '');
        $this->writeLine($this->stdout, sprintf('Collected at %s for environment %s.', $snapshot->collectedAt, $snapshot->environment));

        return self::SUCCESS;
    }

    private function collectors(): int
    {
        foreach ($this->monitor->collectors() as $key => $collector) {
            $this->writeLine($this->stdout, sprintf(
                '%-20s %-3s %s',
                $key,
                $collector->supported() ? 'yes' : 'no',
                get_class($collector)
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @param  resource  $stream
     */
    private function usage($stream): void
    {
        $this->writeLine($stream, 'mindtwo server monitoring');
        $this->writeLine($stream, '');
        $this->writeLine($stream, 'Usage:');
        $this->writeLine($stream, '  monitor push [--dry-run]      Build and deliver a snapshot (cron this daily)');
        $this->writeLine($stream, '  monitor show                  Human-readable snapshot overview');
        $this->writeLine($stream, '  monitor snapshot [--compact]  Print the snapshot JSON');
        $this->writeLine($stream, '  monitor collectors            List collectors and their support status');
    }

    private function summarize(CollectionResult $result): string
    {
        if ($result->error !== null) {
            return mb_substr($result->error, 0, 60);
        }

        if (isset($result->data['technology'])) {
            $version = $result->data['version'] ?? null;

            return trim((string) $result->data['technology'].' '.(is_scalar($version) ? (string) $version : ''));
        }

        if (isset($result->data['count'])) {
            return $result->data['count'].' packages';
        }

        return '';
    }

    /**
     * @param  resource  $stream
     */
    private function writeLine($stream, string $line): void
    {
        fwrite($stream, $line.PHP_EOL);
    }
}
