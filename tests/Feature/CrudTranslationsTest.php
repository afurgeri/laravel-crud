<?php

use Illuminate\Support\Facades\File;
use Modules\Crud\CrudTranslations;

test('it sends the package translations in the application locale', function () {
    app()->setLocale('es');

    expect(app(CrudTranslations::class)->all())
        ->toHaveKey('Actions', 'Acciones')
        ->toHaveKey('Log out', 'Cerrar sesión');
});

test('it includes the translations other packages registered', function () {
    $directory = sys_get_temp_dir().'/crud-translations-'.uniqid();
    File::ensureDirectoryExists($directory);
    File::put("{$directory}/es.json", json_encode(['Roles' => 'Perfiles']));
    app('translator')->addJsonPath($directory);
    app()->setLocale('es');

    try {
        expect(app(CrudTranslations::class)->all())->toHaveKey('Roles', 'Perfiles');
    } finally {
        File::deleteDirectory($directory);
    }
});

test('it falls back to the fallback locale for missing keys', function () {
    $directory = sys_get_temp_dir().'/crud-translations-'.uniqid();
    File::ensureDirectoryExists($directory);
    File::put("{$directory}/en.json", json_encode(['Only in English' => 'Only in English', 'Actions' => 'Actions']));
    app('translator')->addJsonPath($directory);
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('es');

    try {
        expect(app(CrudTranslations::class)->all())
            ->toHaveKey('Only in English', 'Only in English')
            ->toHaveKey('Actions', 'Acciones');
    } finally {
        File::deleteDirectory($directory);
    }
});
