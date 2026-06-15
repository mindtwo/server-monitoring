<?php

declare(strict_types=1);

namespace Mindtwo\Monitoring\Server\Collectors;

use Mindtwo\Monitoring\Collectors\AbstractCollector;
use Mindtwo\Monitoring\Contracts\ProcessRunner;
use Mindtwo\Monitoring\Data\CollectionResult;
use Mindtwo\Monitoring\Process\ProcessRunnerFactory;

/**
 * Load averages and uptime — the server-level vitals beyond the base
 * system stats. Values that cannot be determined are reported as null.
 */
final class ServerLoadCollector extends AbstractCollector
{
    private ProcessRunner $processRunner;

    public function __construct(
        ?ProcessRunner $processRunner = null,
        private ?string $osFamily = null,
        private string $uptimePath = '/proc/uptime'
    ) {
        $this->processRunner = $processRunner ?? ProcessRunnerFactory::make();
    }

    public function key(): string
    {
        return 'load';
    }

    public function collect(): CollectionResult
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;

        if ($load === false) {
            $load = [null, null, null];
        }

        return CollectionResult::ok($this->key(), [
            'load_1m' => $load[0] !== null ? round($load[0], 2) : null,
            'load_5m' => $load[1] !== null ? round($load[1], 2) : null,
            'load_15m' => $load[2] !== null ? round($load[2], 2) : null,
            'uptime_seconds' => $this->uptimeSeconds(),
        ]);
    }

    private function uptimeSeconds(): ?int
    {
        $family = $this->osFamily ?? PHP_OS_FAMILY;

        if ($family === 'Linux' && is_readable($this->uptimePath)) {
            $contents = trim((string) file_get_contents($this->uptimePath));

            if (preg_match('/^(\d+(?:\.\d+)?)/', $contents, $matches) === 1) {
                return (int) round((float) $matches[1]);
            }
        }

        if ($family === 'Darwin' && $this->processRunner->available()) {
            // "{ sec = 1718000000, usec = 123456 } Mon Jun 10 09:00:00 2026"
            $result = $this->processRunner->run(['sysctl', '-n', 'kern.boottime'], 5);

            if ($result->successful && preg_match('/sec\s*=\s*(\d+)/', $result->output, $matches) === 1) {
                return max(0, time() - (int) $matches[1]);
            }
        }

        return null;
    }
}
