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
        ->toContain('schema.form_mode === \'page\'')
        ->toContain('schema.operations.show')
        ->toContain('schema.operations.create')
        ->toContain('schema.operations.update')
        ->toContain('schema.operations.delete')
        ->toContain('canShowRecord')
        ->toContain('show.href(record)')
        ->and($types)
        ->toContain("form_mode: 'dialog' | 'page';")
        ->toContain('operations:')
        ->toContain('export type CrudShowConfig<T extends CrudRecord>')
        ->toContain('id: string | number;');
});
