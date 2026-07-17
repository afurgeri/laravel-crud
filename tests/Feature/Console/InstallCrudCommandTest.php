<?php

test('crud install command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'crud:install'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Install the generic CRUD frontend components and types');
});
