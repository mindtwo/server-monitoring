<?php

declare(strict_types=1);

use Mindtwo\Monitoring\Process\ExecutableFinder;
use Mindtwo\Monitoring\Server\Collectors\DockerCollector;
use Mindtwo\Monitoring\Server\Collectors\ServerLoadCollector;
use Mindtwo\Monitoring\Server\Tests\Fakes\FakeProcessRunner;

function dockerBinDir(): string
{
    $dir = sys_get_temp_dir().'/m2-server-bins-'.uniqid();
    mkdir($dir, 0755, true);
    file_put_contents($dir.'/docker', "#!/bin/sh\nexit 0\n");
    chmod($dir.'/docker', 0755);

    return $dir;
}

test('docker version is parsed and resolved to docker-engine', function () {
    $bin = dockerBinDir();

    $runner = (new FakeProcessRunner)
        ->onOutput('docker --version', "Docker version 26.1.0, build 9714adc\n");

    $result = (new DockerCollector($runner, new ExecutableFinder($bin, [])))->collect();

    expect($result->status)->toBe('ok')
        ->and($result->data)->toBe(['technology' => 'docker-engine', 'version' => '26.1.0']);

    unlink($bin.'/docker');
    rmdir($bin);
});

test('a server without docker reports unsupported', function () {
    $collector = new DockerCollector(new FakeProcessRunner, new ExecutableFinder('', []));

    expect($collector->supported())->toBeFalse()
        ->and($collector->collect()->status)->toBe('unsupported');
});

test('load averages and uptime are collected on linux', function () {
    $uptime = tempnam(sys_get_temp_dir(), 'm2-uptime-');
    file_put_contents($uptime, "123456.78 901234.56\n");

    $result = (new ServerLoadCollector(new FakeProcessRunner, 'Linux', $uptime))->collect();

    expect($result->status)->toBe('ok')
        ->and($result->data['uptime_seconds'])->toBe(123457)
        ->and($result->data)->toHaveKeys(['load_1m', 'load_5m', 'load_15m']);

    unlink($uptime);
});

test('uptime on macos comes from kern.boottime', function () {
    $bootSeconds = time() - 3600;

    $runner = (new FakeProcessRunner)
        ->onOutput('sysctl -n kern.boottime', "{ sec = $bootSeconds, usec = 123456 } Mon Jun 10 09:00:00 2026\n");

    $result = (new ServerLoadCollector($runner, 'Darwin'))->collect();

    expect($result->data['uptime_seconds'])->toBeGreaterThanOrEqual(3599)
        ->and($result->data['uptime_seconds'])->toBeLessThanOrEqual(3602);
});

test('unknown platforms degrade to null uptime without failing', function () {
    $result = (new ServerLoadCollector(new FakeProcessRunner(available: false), 'Windows'))->collect();

    expect($result->status)->toBe('ok')
        ->and($result->data['uptime_seconds'])->toBeNull();
});
