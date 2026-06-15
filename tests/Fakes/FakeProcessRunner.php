<?php

declare(strict_types=1);

namespace Mindtwo\Monitoring\Server\Tests\Fakes;

use Mindtwo\Monitoring\Contracts\ProcessRunner;
use Mindtwo\Monitoring\Data\ProcessResult;

/**
 * Pattern-matching process double (binary directory stripped before
 * matching, so absolute fake-bin paths don't leak into expectations).
 */
final class FakeProcessRunner implements ProcessRunner
{
    /** @var array<int, array{pattern: string, result: ProcessResult}> */
    private array $handlers = [];

    public function __construct(private bool $available = true) {}

    public function onOutput(string $pattern, string $output): self
    {
        $this->handlers[] = ['pattern' => $pattern, 'result' => new ProcessResult(true, $output)];

        return $this;
    }

    public function available(): bool
    {
        return $this->available;
    }

    public function run(array $command, ?int $timeoutSeconds = 15): ProcessResult
    {
        if ($command !== []) {
            $command[0] = basename($command[0]);
        }

        $joined = implode(' ', $command);

        foreach ($this->handlers as $handler) {
            if (str_contains($joined, $handler['pattern'])) {
                return $handler['result'];
            }
        }

        return new ProcessResult(false, '', 'No fake result registered for: '.$joined, 127);
    }
}
