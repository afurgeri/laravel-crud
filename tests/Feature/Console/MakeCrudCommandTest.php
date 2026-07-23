<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::delete(base_path('app/Models/RootSqlWidget.php'));
    File::delete(base_path('app/Crud/RootSqlWidgetCrudDefinition.php'));
    File::delete(base_path('app/Http/Controllers/RootSqlWidgetController.php'));
    File::delete(base_path('app/Policies/RootSqlWidgetPolicy.php'));
    File::delete(base_path('app/Models/RootMongoWidget.php'));
    File::delete(base_path('app/Crud/RootMongoWidgetCrudDefinition.php'));
    File::delete(base_path('app/Http/Controllers/RootMongoWidgetController.php'));
    File::delete(base_path('app/Policies/RootMongoWidgetPolicy.php'));
    File::delete(base_path('database/factories/RootSqlWidgetFactory.php'));
    File::delete(base_path('database/factories/RootMongoWidgetFactory.php'));
    File::delete(base_path('app/Models/People.php'));
    File::delete(base_path('app/Crud/PeopleCrudDefinition.php'));
    File::delete(base_path('app/Http/Controllers/PeopleController.php'));
    File::delete(base_path('app/Policies/PeoplePolicy.php'));
    File::delete(base_path('database/factories/PeopleFactory.php'));
    File::delete(base_path('app/Models/RbacWidget.php'));
    File::delete(base_path('app/Crud/RbacWidgetCrudDefinition.php'));
    File::delete(base_path('app/Http/Controllers/RbacWidgetController.php'));
    File::delete(base_path('app/Policies/RbacWidgetPolicy.php'));
    File::delete(base_path('app/Permissions/RbacWidgetPermissions.php'));
    File::delete(base_path('database/factories/RbacWidgetFactory.php'));
    File::delete(base_path('database/factories/ModuleWidgetFactory.php'));
    File::delete(base_path('database/seeders/RbacWidgetPermissionSeeder.php'));
    File::deleteDirectory(base_path('modules/ModuleWidgets'));
    File::deleteDirectory(base_path('tests/Feature/ModuleWidgets'));
    File::deleteDirectory(base_path('tests/Feature/Crud'));
    File::deleteDirectory(base_path('resources/js/pages/root-sql-widgets'));
    File::deleteDirectory(base_path('resources/js/pages/root-mongo-widgets'));
    File::deleteDirectory(base_path('resources/js/pages/people'));
    File::deleteDirectory(base_path('resources/js/pages/rbac-widgets'));
    File::deleteDirectory(base_path('resources/js/pages/module-widgets'));

    foreach (File::glob(base_path('database/migrations/*_create_root_sql_widgets_table.php')) as $migration) {
        File::delete($migration);
    }

    foreach (File::glob(base_path('database/migrations/*_create_root_mongo_widgets_table.php')) as $migration) {
        File::delete($migration);
    }

    foreach (File::glob(base_path('database/migrations/*_create_people_table.php')) as $migration) {
        File::delete($migration);
    }

    foreach (File::glob(base_path('database/migrations/*_create_rbac_widgets_table.php')) as $migration) {
        File::delete($migration);
    }
});

test('make crud command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'make:crud'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Scaffold a complete CRUD admin screen')
        ->expectsOutputToContain('--database')
        ->expectsOutputToContain('--rbac')
        ->expectsOutputToContain('--test');
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

test('make crud rejects an invalid module identifier before generating files', function () {
    $this->artisan('make:crud', [
        'name' => 'InvalidModuleWidget',
        '--module' => '../Tasks',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain('The --module option must be a valid module identifier');

    expect(File::exists(base_path('modules/Tasks/src/Models/InvalidModuleWidget.php')))->toBeFalse();
});

test('make crud generates an application-scoped SQL resource when no module is provided', function () {
    $routesPath = base_path('routes/web.php');
    File::ensureDirectoryExists(dirname($routesPath));
    File::put($routesPath, <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
PHP);
    $routes = File::get($routesPath);
    $composer = File::get(base_path('composer.json'));
    $providers = File::get(base_path('bootstrap/providers.php'));

    try {
        $this->artisan('make:crud', [
            'name' => 'RootSqlWidget',
            '--test' => true,
        ])->assertExitCode(0);

        expect(File::get(base_path('app/Models/RootSqlWidget.php')))
            ->toContain('namespace App\\Models;')
            ->toContain('use App\\Crud\\RootSqlWidgetCrudDefinition;')
            ->toContain('use App\\Policies\\RootSqlWidgetPolicy;')
            ->and(File::get(base_path('app/Crud/RootSqlWidgetCrudDefinition.php')))
            ->toContain('namespace App\\Crud;')
            ->toContain('use App\\Models\\RootSqlWidget;')
            ->toContain("CrudField::make('name', ['required', 'string', 'max:255'])->unique()")
            ->and(File::get(base_path('app/Crud/RootSqlWidgetCrudDefinition.php')))->toContain('HasDefaultCrudPageSize')
            ->and(File::get(base_path('app/Policies/RootSqlWidgetPolicy.php')))->not->toContain('Modules\\Rbac\\Contracts\\HasPermissions')
            ->and(File::exists(base_path('app/Http/Controllers/RootSqlWidgetController.php')))->toBeTrue()
            ->and(File::exists(base_path('app/Policies/RootSqlWidgetPolicy.php')))->toBeTrue()
            ->and(File::exists(base_path('database/factories/RootSqlWidgetFactory.php')))->toBeTrue()
            ->and(File::exists(base_path('tests/Feature/Crud/RootSqlWidgetCrudDefinitionTest.php')))->toBeTrue()
            ->and(File::exists(base_path('resources/js/pages/root-sql-widgets/Index.vue')))->toBeTrue()
            ->and(File::get(base_path('resources/js/pages/root-sql-widgets/Index.vue')))
            ->toContain('rootSqlWidgetsIndex')
            ->not->toContain('root-sql-widgetsIndex')
            ->and(File::exists(base_path('resources/js/pages/root-sql-widgets/Create.vue')))->toBeTrue()
            ->and(File::exists(base_path('resources/js/pages/root-sql-widgets/Edit.vue')))->toBeTrue()
            ->and(File::glob(base_path('database/migrations/*_create_root_sql_widgets_table.php')))->not->toBeEmpty()
            ->and(File::isDirectory(base_path('modules/RootSqlWidgets')))->toBeFalse()
            ->and(File::get(base_path('composer.json')))->toBe($composer)
            ->and(File::get(base_path('bootstrap/providers.php')))->toBe($providers)
            ->and(File::get($routesPath))->toContain('use App\\Http\\Controllers\\RootSqlWidgetController;')
            ->toContain("Route::resource('root-sql-widgets', RootSqlWidgetController::class)")
            ->toContain("['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']");
    } finally {
        File::put($routesPath, $routes);
        File::delete($routesPath);
    }
});

test('make crud resolves the module placeholder in generated module files', function () {
    $composerPath = base_path('composer.json');
    $providersPath = base_path('bootstrap/providers.php');
    $composer = File::get($composerPath);
    $providersExisted = File::exists($providersPath);
    $providers = File::get($providersPath);
    File::put($providersPath, <<<'PHP'
<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
];
PHP);

    try {
        $this->artisan('make:crud', [
            'name' => 'ModuleWidget',
            '--module' => 'ModuleWidgets',
        ])->assertExitCode(0);

        expect(File::get(base_path('modules/ModuleWidgets/routes/web.php')))
            ->toContain('use Modules\\ModuleWidgets\\Http\\Controllers\\ModuleWidgetController;')
            ->not->toContain('{{ module }}')
            ->and(File::get(base_path('modules/ModuleWidgets/src/ModuleWidgetsServiceProvider.php')))
            ->toContain('namespace Modules\\ModuleWidgets;')
            ->toContain('class ModuleWidgetsServiceProvider')
            ->not->toContain('{{ module }}');
    } finally {
        File::put($composerPath, $composer);

        if ($providersExisted) {
            File::put($providersPath, $providers);
        } else {
            File::delete($providersPath);
        }
    }
});

test('make crud generates an application-scoped MongoDB resource when no module is provided', function () {
    if (! class_exists('MongoDB\\Laravel\\Eloquent\\Model')) {
        class_alias(Model::class, 'MongoDB\\Laravel\\Eloquent\\Model');
    }

    $routesPath = base_path('routes/web.php');
    File::ensureDirectoryExists(dirname($routesPath));
    File::put($routesPath, <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
PHP);
    $routes = File::get($routesPath);
    $composer = File::get(base_path('composer.json'));
    $providers = File::get(base_path('bootstrap/providers.php'));

    try {
        $this->artisan('make:crud', [
            'name' => 'RootMongoWidget',
            '--database' => 'mongodb',
            '--force' => true,
        ])->assertExitCode(0);

        expect(File::get(base_path('app/Models/RootMongoWidget.php')))
            ->toContain('namespace App\\Models;')
            ->toContain('use App\\Crud\\RootMongoWidgetCrudDefinition;')
            ->toContain('use App\\Policies\\RootMongoWidgetPolicy;')
            ->toContain('use MongoDB\\Laravel\\Eloquent\\Model;')
            ->toContain("protected \$collection = 'root_mongo_widgets';")
            ->and(File::exists(base_path('app/Crud/RootMongoWidgetCrudDefinition.php')))->toBeTrue()
            ->and(File::exists(base_path('app/Http/Controllers/RootMongoWidgetController.php')))->toBeTrue()
            ->and(File::exists(base_path('app/Policies/RootMongoWidgetPolicy.php')))->toBeTrue()
            ->and(File::glob(base_path('database/migrations/*_create_root_mongo_widgets_table.php')))->toBeEmpty()
            ->and(File::isDirectory(base_path('modules/RootMongoWidgets')))->toBeFalse()
            ->and(File::get(base_path('composer.json')))->toBe($composer)
            ->and(File::get(base_path('bootstrap/providers.php')))->toBe($providers)
            ->and(File::get($routesPath))->toContain('use App\\Http\\Controllers\\RootMongoWidgetController;')
            ->toContain("Route::resource('root-mongo-widgets', RootMongoWidgetController::class)");
    } finally {
        File::put($routesPath, $routes);
        File::delete($routesPath);
    }
});

test('make crud matches resource route binding parameters when the entity is plural', function () {
    $routesPath = base_path('routes/web.php');
    File::ensureDirectoryExists(dirname($routesPath));
    File::put($routesPath, <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
PHP);
    $routes = File::get($routesPath);

    try {
        $this->artisan('make:crud', [
            'name' => 'People',
        ])->assertExitCode(0);

        expect(File::get(base_path('app/Http/Controllers/PeopleController.php')))
            ->toContain('public function update(Request $request, People $person, CrudMutationManager $mutations)')
            ->toContain('$definition->authorizeUpdate($person);')
            ->toContain('$mutations->update($person, People::makeCrudDefinition(), $request->all());')
            ->toContain('public function destroy(People $person, CrudMutationManager $mutations)')
            ->toContain('$mutations->delete($person, People::makeCrudDefinition());');
    } finally {
        File::put($routesPath, $routes);
        File::delete($routesPath);
    }
});

test('make crud generates RBAC permission artifacts when requested', function () {
    if (! interface_exists('Modules\\Rbac\\Contracts\\HasPermissions')) {
        eval('namespace Modules\\Rbac\\Contracts; interface HasPermissions { public function hasPermission(string $permission): bool; }');
    }

    if (! class_exists('Modules\\Rbac\\RbacModels')) {
        eval('namespace Modules\\Rbac; final class RbacModels {}');
    }

    $routesPath = base_path('routes/web.php');
    File::ensureDirectoryExists(dirname($routesPath));
    File::put($routesPath, <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
PHP);
    $routes = File::get($routesPath);

    try {
        $this->artisan('make:crud', [
            'name' => 'RbacWidget',
            '--rbac' => true,
        ])->assertExitCode(0);

        expect(File::get(base_path('app/Permissions/RbacWidgetPermissions.php')))
            ->toContain('namespace App\\Permissions;')
            ->toContain("public const VIEW = 'rbac-widgets.view';")
            ->toContain("public const CREATE = 'rbac-widgets.create';")
            ->toContain("public const UPDATE = 'rbac-widgets.update';")
            ->toContain("public const DELETE = 'rbac-widgets.delete';")
            ->and(File::get(base_path('app/Policies/RbacWidgetPolicy.php')))
            ->toContain('use Modules\\Rbac\\Contracts\\HasPermissions;')
            ->toContain('return $user->hasPermission(RbacWidgetPermissions::VIEW);')
            ->toContain('return $user->hasPermission(RbacWidgetPermissions::CREATE);')
            ->toContain('return $user->hasPermission(RbacWidgetPermissions::UPDATE);')
            ->toContain('return $user->hasPermission(RbacWidgetPermissions::DELETE);')
            ->and(File::get(base_path('database/seeders/RbacWidgetPermissionSeeder.php')))
            ->toContain('namespace Database\\Seeders;')
            ->toContain('foreach (RbacWidgetPermissions::all() as $permission)')
            ->toContain("if (config('rbac.storage', 'mysql') === 'mongodb')");
    } finally {
        File::put($routesPath, $routes);
        File::delete($routesPath);
    }
});
