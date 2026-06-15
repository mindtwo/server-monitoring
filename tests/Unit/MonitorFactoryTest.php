<?php

declare(strict_types=1);

use Mindtwo\Monitoring\Server\MonitorFactory;

test('the monitor combines the base catalog with the server collectors', function () {
    $monitor = MonitorFactory::make(serverConfig(), sys_get_temp_dir());
    $keys = array_keys($monitor->collectors());

    foreach (['os', 'php', 'system', 'composer_packages', 'git'] as $baseKey) {
        expect($keys)->toContain($baseKey);
    }

    foreach (['docker', 'load'] as $serverKey) {
        expect($keys)->toContain($serverKey);
    }
});

test('a snapshot through the factory carries the server source', function () {
    $payload = MonitorFactory::make(serverConfig(), sys_get_temp_dir())->snapshot()->toArray();

    expect($payload['source']['type'])->toBe('server')
        ->and($payload['source']['package'])->toBe('mindtwo/server-monitoring')
        ->and($payload['project_key'])->toBe('prj_test')
        ->and($payload['metrics'])->toHaveKeys(['load', 'docker']);
});
