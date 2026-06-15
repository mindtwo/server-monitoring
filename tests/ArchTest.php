<?php

declare(strict_types=1);

test('all source files declare strict types')
    ->expect('Mindtwo\Monitoring\Server')
    ->toUseStrictTypes();

test('no debug or dangerous shell helpers are used')
    ->expect('Mindtwo\Monitoring\Server')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray', 'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'eval']);

test('the project never couples to a framework')
    ->expect('Mindtwo\Monitoring\Server')
    ->not->toUse(['Illuminate', 'Laravel', 'craft', 'WordPress']);
