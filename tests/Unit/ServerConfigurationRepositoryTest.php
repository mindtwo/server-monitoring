<?php

declare(strict_types=1);

test('credentials come from the environment', function () {
    $credentials = serverConfig()->credentials();

    expect($credentials->projectKey)->toBe('prj_test')
        ->and($credentials->secret)->toBe('test-secret')
        ->and($credentials->isComplete())->toBeTrue();
});

test('secure defaults apply when nothing is configured', function () {
    $config = serverConfig(['MONITORING_PROJECT_KEY' => '', 'MONITORING_SECRET' => '']);

    expect($config->credentials()->isComplete())->toBeFalse()
        ->and($config->endpoint())->toBe('https://monitoring.mindtwo.com/api/monitoring')
        ->and($config->ipAllowList())->toBe([])
        ->and($config->enabled())->toBeTrue()
        ->and($config->routeEnabled())->toBeTrue()
        ->and($config->integer('signature_tolerance'))->toBe(300)
        ->and($config->integer('timeout'))->toBe(15)
        ->and($config->get('environment'))->toBe('production');
});

test('endpoint and environment are overridable', function () {
    $config = serverConfig([
        'MONITORING_ENDPOINT' => 'https://staging.example/ingest',
        'MONITORING_ENVIRONMENT' => 'staging',
    ]);

    expect($config->endpoint())->toBe('https://staging.example/ingest')
        ->and($config->get('environment'))->toBe('staging');
});

test('the ip allow-list parses comma-separated values', function () {
    $config = serverConfig(['MONITORING_IP_ALLOW_LIST' => ' 10.0.0.0/8 , 203.0.113.10 ,, ']);

    expect($config->ipAllowList())->toBe(['10.0.0.0/8', '203.0.113.10']);
});

test('boolean flags understand string representations', function () {
    expect(serverConfig(['MONITORING_ENABLED' => 'false'])->enabled())->toBeFalse()
        ->and(serverConfig(['MONITORING_ENABLED' => '0'])->routeEnabled())->toBeFalse()
        ->and(serverConfig(['MONITORING_ROUTE_ENABLED' => 'off'])->routeEnabled())->toBeFalse()
        ->and(serverConfig(['MONITORING_ROUTE_ENABLED' => 'yes'])->routeEnabled())->toBeTrue();
});
