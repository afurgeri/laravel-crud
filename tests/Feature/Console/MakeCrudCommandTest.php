<?php

test('make crud command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'make:crud'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Scaffold a complete CRUD admin screen')
        ->expectsOutputToContain('--database');
});

test('make crud rejects an unknown database connector', function () {
    $this->artisan('make:crud', [
        'name' => 'Person',
        '--module' => 'People',
        '--database' => 'pgsql',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain('The --database option must be either mysql or mongodb.');
});
