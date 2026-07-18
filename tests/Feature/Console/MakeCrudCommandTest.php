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
    File::deleteDirectory(base_path('tests/Feature/Crud'));
    File::deleteDirectory(base_path('resources/js/pages/root-sql-widgets'));
    File::deleteDirectory(base_path('resources/js/pages/root-mongo-widgets'));
    File::deleteDirectory(base_path('resources/js/pages/people'));

    foreach (File::glob(base_path('database/migrations/*_create_root_sql_widgets_table.php')) as $migration) {
        File::delete($migration);
    }

    foreach (File::glob(base_path('database/migrations/*_create_root_mongo_widgets_table.php')) as $migration) {
        File::delete($migration);
    }

    foreach (File::glob(base_path('database/migrations/*_create_people_table.php')) as $migration) {
        File::delete($migration);
    }
});

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
        ])->assertExitCode(0);

        expect(File::get(base_path('app/Models/RootSqlWidget.php')))
            ->toContain('namespace App\\Models;')
            ->toContain('use App\\Crud\\RootSqlWidgetCrudDefinition;')
            ->toContain('use App\\Policies\\RootSqlWidgetPolicy;')
            ->and(File::get(base_path('app/Crud/RootSqlWidgetCrudDefinition.php')))
            ->toContain('namespace App\\Crud;')
            ->toContain('use App\\Models\\RootSqlWidget;')
            ->and(File::exists(base_path('app/Http/Controllers/RootSqlWidgetController.php')))->toBeTrue()
            ->and(File::exists(base_path('app/Policies/RootSqlWidgetPolicy.php')))->toBeTrue()
            ->and(File::exists(base_path('database/factories/RootSqlWidgetFactory.php')))->toBeTrue()
            ->and(File::exists(base_path('tests/Feature/Crud/RootSqlWidgetCrudDefinitionTest.php')))->toBeTrue()
            ->and(File::exists(base_path('resources/js/pages/root-sql-widgets/Index.vue')))->toBeTrue()
            ->and(File::glob(base_path('database/migrations/*_create_root_sql_widgets_table.php')))->not->toBeEmpty()
            ->and(File::isDirectory(base_path('modules/RootSqlWidgets')))->toBeFalse()
            ->and(File::get(base_path('composer.json')))->toBe($composer)
            ->and(File::get(base_path('bootstrap/providers.php')))->toBe($providers)
            ->and(File::get($routesPath))->toContain('use App\\Http\\Controllers\\RootSqlWidgetController;')
            ->toContain("Route::resource('root-sql-widgets', RootSqlWidgetController::class)");
    } finally {
        File::put($routesPath, $routes);
        File::delete($routesPath);
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
