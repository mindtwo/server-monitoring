<?php

declare(strict_types=1);

use Mindtwo\Monitoring\Server\Env;
use Mindtwo\Monitoring\Server\ServerConfigurationRepository;

function serverConfig(array $env = []): ServerConfigurationRepository
{
    return new ServerConfigurationRepository(new Env(array_merge([
        'MONITORING_PROJECT_KEY' => 'prj_test',
        'MONITORING_SECRET' => 'test-secret',
    ], $env)));
}
