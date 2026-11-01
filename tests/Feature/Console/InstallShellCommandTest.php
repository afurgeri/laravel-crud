<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->shellRoot = dirname(__DIR__, 3).'/resources/shell';
    $this->appRoot = sys_get_temp_dir().'/crud-shell-'.uniqid();
    File::ensureDirectoryExists($this->appRoot);
    $this->originalBasePath = $this->app->basePath();
    $this->app->setBasePath($this->appRoot);
    $this->app->useLangPath($this->appRoot.'/lang');
});

afterEach(function () {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->appRoot);
});

test('it installs the shell components and the application menu', function () {
    $this->artisan('crud:install-shell')->assertExitCode(0);

    expect(File::get(base_path('resources/js/components/AppSidebar.vue')))
        ->toBe(File::get($this->shellRoot.'/js/components/AppSidebar.vue'))
        ->and(File::get(base_path('resources/js/components/ui/sidebar/SidebarGroupLabel.vue')))
        ->toContain('group-data-[collapsible=icon]:pointer-events-none')
        ->and(File::get(base_path('resources/js/navigation.ts')))
        ->toBe(File::get($this->shellRoot.'/stubs/navigation.ts'));
});

test('it keeps changed components unless forced', function () {
    $target = base_path('resources/js/components/NavMain.vue');
    File::ensureDirectoryExists(dirname($target));
    File::put($target, 'custom menu');

    $this->artisan('crud:install-shell')->assertExitCode(0);

    expect(File::get($target))->toBe('custom menu');

    $this->artisan('crud:install-shell', ['--force' => true])->assertExitCode(0);

    expect(File::get($target))->toBe(File::get($this->shellRoot.'/js/components/NavMain.vue'));
});

test('it never replaces the application menu', function () {
    $target = base_path('resources/js/navigation.ts');
    File::ensureDirectoryExists(dirname($target));
    File::put($target, 'export const menu = [];');

    $this->artisan('crud:install-shell', ['--force' => true])->assertExitCode(0);

    expect(File::get($target))->toBe('export const menu = [];');
});

test('it leaves a sidebar that still holds its own menu for a manual move', function () {
    $target = base_path('resources/js/components/AppSidebar.vue');
    File::ensureDirectoryExists(dirname($target));
    File::put($target, 'sidebar with inline menu groups');

    $this->artisan('crud:install-shell', ['--force' => true])
        ->expectsOutputToContain('Move the menu groups')
        ->assertExitCode(0);

    $this->artisan('crud:install-shell', ['--force' => true])
        ->expectsOutputToContain('Move the menu groups')
        ->assertExitCode(0);

    expect(File::get($target))->toBe('sidebar with inline menu groups');
});

test('it replaces the sidebar once the menu was moved into navigation.ts', function () {
    $sidebar = base_path('resources/js/components/AppSidebar.vue');
    File::ensureDirectoryExists(dirname($sidebar));
    File::put($sidebar, 'sidebar with inline menu groups');
    File::put(base_path('resources/js/navigation.ts'), 'export function navigationGroups() { return []; }');

    $this->artisan('crud:install-shell')->assertExitCode(0);

    expect(File::get($sidebar))->toBe('sidebar with inline menu groups');

    $this->artisan('crud:install-shell', ['--force' => true])->assertExitCode(0);

    expect(File::get($sidebar))->toBe(File::get($this->shellRoot.'/js/components/AppSidebar.vue'));
});

test('it sets up the spanish locale', function () {
    File::put(base_path('.env.example'), "APP_LOCALE=en\nAPP_FALLBACK_LOCALE=en\n");
    File::put(base_path('phpunit.xml'), "<php>\n        <env name=\"APP_ENV\" value=\"testing\"/>\n</php>\n");

    $this->artisan('crud:install-shell', ['--locale' => 'es'])->assertExitCode(0);

    expect(File::exists(lang_path('es/validation.php')))->toBeTrue()
        ->and(File::get(base_path('.env.example')))->toBe("APP_LOCALE=es\nAPP_FALLBACK_LOCALE=es\n")
        ->and(File::get(base_path('phpunit.xml')))->toContain('<env name="APP_LOCALE" value="en" force="true"/>');
});

test('it rejects a locale it has no translations for', function () {
    $this->artisan('crud:install-shell', ['--locale' => 'fr'])
        ->expectsOutputToContain('The [fr] locale is not supported')
        ->assertExitCode(1);

    expect(File::exists(base_path('resources/js/components/AppSidebar.vue')))->toBeFalse();
});
