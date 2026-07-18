<?php

use Illuminate\Support\Facades\File;
use Tests\MongoDbTestCase;

uses(MongoDbTestCase::class);

beforeEach(function () {
    File::ensureDirectoryExists(base_path('modules/MongoWidgets/routes'));
    File::put(base_path('modules/MongoWidgets/routes/web.php'), <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::middleware([])->group(function (): void {
});
PHP);
});

afterEach(function () {
    File::deleteDirectory(base_path('modules/MongoWidgets'));
    File::deleteDirectory(base_path('resources/js/pages/mongo-widgets'));
    File::delete(base_path('database/factories/MongoWidgetFactory.php'));
    File::deleteDirectory(base_path('tests/Feature/MongoWidgets'));
});

test('make crud generates a MongoDB model without an SQL migration', function () {
    $this->artisan('make:crud', [
        'name' => 'MongoWidget',
        '--module' => 'MongoWidgets',
        '--database' => 'mongodb',
    ])->assertExitCode(0);

    $model = base_path('modules/MongoWidgets/src/Models/MongoWidget.php');

    expect(File::exists($model))->toBeTrue()
        ->and(File::get($model))->toContain('use MongoDB\\Laravel\\Eloquent\\Model;')
        ->toContain("protected \$collection = 'mongo_widgets';")
        ->and(File::glob(base_path('modules/MongoWidgets/database/migrations/*.php')))->toBeEmpty();
});
