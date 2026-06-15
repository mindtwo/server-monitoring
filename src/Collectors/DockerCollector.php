<?php

declare(strict_types=1);

namespace Mindtwo\Monitoring\Server\Collectors;

use Mindtwo\Monitoring\Collectors\BinaryVersionCollector;

/**
 * Docker Engine version via the docker CLI.
 */
final class DockerCollector extends BinaryVersionCollector
{
    public function key(): string
    {
        return 'docker';
    }

    protected function binaries(): array
    {
        return ['docker'];
    }

    protected function technologyIdentifier(): string
    {
        // Resolved to "docker-engine" through the default alias map.
        return 'docker';
    }

    protected function parseVersion(string $output): ?string
    {
        // "Docker version 26.1.0, build 9714adc"
        return preg_match('/Docker version (\d+(?:\.\d+)*)/i', $output, $matches) === 1 ? $matches[1] : null;
    }
}
