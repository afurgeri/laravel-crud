<?php

test('make crud command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'make:crud'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Scaffold a complete CRUD admin screen');
});
