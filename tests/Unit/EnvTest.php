<?php

declare(strict_types=1);

use Mindtwo\Monitoring\Server\Env;

test('env files are parsed with quotes, comments and export prefixes', function () {
    $values = Env::parse(<<<'ENV'
    # Monitoring credentials
    MONITORING_PROJECT_KEY=prj_live_8f3a
    MONITORING_SECRET="quoted secret value"
    export MONITORING_ENDPOINT='https://example.com/ingest'
    MONITORING_TIMEOUT=30 # inline comment
    EMPTY=
    INVALID LINE WITHOUT EQUALS
    1INVALID_NAME=x

    ENV);

    expect($values)->toBe([
        'MONITORING_PROJECT_KEY' => 'prj_live_8f3a',
        'MONITORING_SECRET' => 'quoted secret value',
        'MONITORING_ENDPOINT' => 'https://example.com/ingest',
        'MONITORING_TIMEOUT' => '30',
        'EMPTY' => '',
    ]);
});

test('real environment variables win over file values', function () {
    putenv('M2_TEST_OVERRIDE=from-real-env');

    $env = new Env(['M2_TEST_OVERRIDE' => 'from-file', 'M2_TEST_FILE_ONLY' => 'file-value']);

    expect($env->get('M2_TEST_OVERRIDE'))->toBe('from-real-env')
        ->and($env->get('M2_TEST_FILE_ONLY'))->toBe('file-value')
        ->and($env->get('M2_TEST_MISSING'))->toBeNull();

    putenv('M2_TEST_OVERRIDE');
});

test('loading a missing file yields an empty env', function () {
    expect(Env::load('/nonexistent/.env')->get('ANYTHING'))->toBeNull();
});

test('loading a real file works end to end', function () {
    $path = tempnam(sys_get_temp_dir(), 'm2-env-');
    file_put_contents($path, "MONITORING_PROJECT_KEY=prj_from_file\n");

    expect(Env::load($path)->get('MONITORING_PROJECT_KEY'))->toBe('prj_from_file');

    unlink($path);
});
