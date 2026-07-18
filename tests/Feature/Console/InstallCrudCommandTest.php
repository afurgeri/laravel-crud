<?php

use Illuminate\Support\Facades\File;

test('crud install command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'crud:install'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Install the generic CRUD frontend components and types');
});

test('crud frontend resources expose the paginator contract', function () {
    $component = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudPage.vue');
    $types = File::get(dirname(__DIR__, 3).'/resources/js/types/crud.ts');

    expect($component)
        ->toContain('records: CrudPaginator<T>;')
        ->toContain(':records="records.data"')
        ->toContain('goToPage')
        ->and($types)
        ->toContain('export type CrudPaginator<T>');
});
