<?php

use Illuminate\Support\Facades\File;

test('crud install command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'crud:install'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Install the generic CRUD frontend components and types')
        ->expectsOutputToContain('--upgrade');
});

test('crud install can preserve existing frontend files while installing missing resources', function () {
    $source = dirname(__DIR__, 3).'/resources/js/composables/useTranslation.ts';
    $target = base_path('resources/js/composables/useTranslation.ts');

    File::ensureDirectoryExists(dirname($target));
    File::put($target, 'custom translation helper');

    try {
        $this->artisan('crud:install', ['--skip-existing' => true])
            ->assertExitCode(0);

        expect(File::get($target))->toBe('custom translation helper')
            ->and(File::get(dirname($source).'/../types/crud.ts'))->toContain('CrudSchema');
    } finally {
        File::delete($target);
    }
});

test('crud install upgrade reports existing files without overwriting them', function () {
    $target = base_path('resources/js/composables/useTranslation.ts');

    File::ensureDirectoryExists(dirname($target));
    File::put($target, 'custom translation helper');

    try {
        $this->artisan('crud:install', ['--upgrade' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('were not overwritten');

        expect(File::get($target))->toBe('custom translation helper');
    } finally {
        File::delete($target);
    }
});

test('crud install copies the translation composable when it is missing', function () {
    $target = base_path('resources/js/composables/useTranslation.ts');

    File::delete($target);

    try {
        $this->artisan('crud:install', ['--skip-existing' => true])
            ->assertExitCode(0);

        expect(File::exists($target))->toBeTrue()
            ->and(File::get($target))->toContain('export function useTranslation()');
    } finally {
        File::delete($target);
    }
});

test('crud frontend resources expose the paginator contract', function () {
    $component = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudPage.vue');
    $field = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudField.vue');
    $combobox = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudCombobox.vue');
    $table = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudTable.vue');
    $filters = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudFilters.vue');
    $form = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudForm.vue');
    $formPage = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudFormPage.vue');
    $page = File::get(dirname(__DIR__, 3).'/resources/js/components/crud/CrudPage.vue');
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
        ->toContain('workspace?: string;')
        ->toContain('v-if="workspace"')
        ->toContain('canShowRecord')
        ->toContain('show.href(record)')
        ->toContain('bg-linear-to-br')
        ->toContain('rounded-2xl')
        ->toContain('schema.search?.enabled || schema.filters?.length > 0')
        ->and($table)
        ->toContain('md:hidden')
        ->toContain('animate-pulse')
        ->and($filters)
        ->toContain('SlidersHorizontal')
        ->toContain('bg-muted/40')
        ->toContain('sm:ml-auto')
        ->toContain('v-if="search.enabled || filters.length > 0"')
        ->and($field)
        ->toContain("import { useTranslation } from '@/composables/useTranslation';")
        ->toContain("{{ t('Add') }}")
        ->toContain('v-if="field.visible"')
        ->toContain('spanClasses(field.span)')
        ->toContain('class="contents"')
        ->toContain('field.confirmed && !$slots.default && !readOnly')
        ->and(File::exists(dirname(__DIR__, 3).'/resources/js/components/crud/CrudFormPage.vue'))->toBeTrue()
        ->and($field)
        ->toContain('CrudFieldSlotProps')
        ->toContain('$slots.default')
        ->and($combobox)
        ->toContain('filteredOptions')
        ->toContain(':ignore-filter="true"')
        ->and($form)
        ->toContain('field-${field.name}')
        ->and($formPage)
        ->toContain('field-${field.name}')
        ->and($page)
        ->toContain('create-field-${field.name}')
        ->toContain('edit-field-${field.name}')
        ->and($types)
        ->and($form)
        ->toContain('initialValues')
        ->toContain('defaultValue')
        ->toContain('fields.filter((field) => field.visible)')
        ->toContain('grid-cols-12')
        ->and($formPage)
        ->toContain('grid-cols-12')
        ->toContain('layoutWidthClasses[schema.form_width]')
        ->and($page)
        ->toContain('layoutWidthClasses[schema.page_width]')
        ->and($types)
        ->toContain('visible: boolean;')
        ->toContain("'2xl'?: number;")
        ->toContain('page_width: CrudLayoutWidth;')
        ->toContain('form_width: CrudLayoutWidth;')
        ->toContain("| 'combobox'")
        ->toContain("form_mode: 'dialog' | 'page';")
        ->toContain('operations:')
        ->toContain('export type CrudShowConfig<T extends CrudRecord>')
        ->toContain('id: string | number;');
});

test('crud frontend resources expose the translation helper and catalog', function () {
    $composable = File::get(dirname(__DIR__, 3).'/resources/js/composables/useTranslation.ts');
    $translations = File::get(dirname(__DIR__, 3).'/resources/lang/en.json');

    expect($composable)
        ->toContain('export function useTranslation()')
        ->toContain('page.props.translations')
        ->and($translations)
        ->toContain('"Add": "Add"')
        ->and($translations)
        ->toContain('"No records found."')
        ->toContain('"Create :name"')
        ->toContain('"Back to :name"')
        ->toContain('"Search records..."');
});
