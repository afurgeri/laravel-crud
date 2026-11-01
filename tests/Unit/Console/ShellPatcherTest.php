<?php

use Modules\Crud\Console\ShellPatcher;

test('it adds the item permission and the menu group to the starter kit navigation types', function () {
    $types = (string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/shell/navigation.ts');

    $patched = (new ShellPatcher)->navigationTypes($types);

    expect($patched)
        ->toContain("    isActive?: boolean;\n    permission?: string;\n};")
        ->toEndWith("export type NavGroup = {\n    title: string;\n    items: NavItem[];\n};\n")
        ->and((new ShellPatcher)->navigationTypes((string) $patched))->toBe($patched);
});

test('it keeps navigation types that already declare the permission', function () {
    $types = "export type NavItem = {\n    title: string;\n    permission?: string;\n    vertical?: string;\n};\n\nexport type NavGroup = {\n    title: string;\n    items: NavItem[];\n};\n";

    expect((new ShellPatcher)->navigationTypes($types))->toBe($types);
});

test('it asks for a manual change when there is no navigation item type', function () {
    expect((new ShellPatcher)->navigationTypes('export type Item = {};'))->toBeNull();
});

test('it appends the base styles once', function () {
    $patcher = new ShellPatcher;
    $styles = $patcher->appStyles("@import 'tailwindcss';\n");

    expect($styles)
        ->toContain("@import 'tailwindcss';\n\n@layer base {\n    ::selection {")
        ->toContain('input::-webkit-date-and-time-value')
        ->and($patcher->appStyles($styles))->toBe($styles);
});

test('it forces english in the test environment once', function () {
    $patcher = new ShellPatcher;
    $phpunit = "<php>\n        <env name=\"APP_ENV\" value=\"testing\"/>\n        <env name=\"BCRYPT_ROUNDS\" value=\"4\"/>\n</php>\n";

    $patched = $patcher->phpunitLocale($phpunit);

    expect($patched)->toBe("<php>\n        <env name=\"APP_ENV\" value=\"testing\"/>\n        <env name=\"APP_LOCALE\" value=\"en\" force=\"true\"/>\n        <env name=\"BCRYPT_ROUNDS\" value=\"4\"/>\n</php>\n")
        ->and($patcher->phpunitLocale((string) $patched))->toBe($patched)
        ->and($patcher->phpunitLocale('<php></php>'))->toBeNull();
});

test('it sets the application locale in the environment file', function () {
    $patcher = new ShellPatcher;

    expect($patcher->environmentLocale("APP_NAME=Laravel\nAPP_LOCALE=en\nAPP_FALLBACK_LOCALE=en\n", 'es'))
        ->toBe("APP_NAME=Laravel\nAPP_LOCALE=es\nAPP_FALLBACK_LOCALE=es\n")
        ->and($patcher->environmentLocale("APP_NAME=Laravel\n", 'es'))
        ->toBe("APP_NAME=Laravel\nAPP_LOCALE=es\nAPP_FALLBACK_LOCALE=es\n");
});
