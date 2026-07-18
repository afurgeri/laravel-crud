<?php

namespace Modules\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class MakeCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {name : The singular StudlyCase entity name, e.g. Person}
        {--module= : The StudlyCase module name, e.g. People (required)}
        {--table= : The database table name, defaults to the snake_case plural of the entity}
        {--database=mysql : The database connector to target (mysql or mongodb)}
        {--force : Overwrite any files that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a complete CRUD admin screen (backend + frontend) for a new entity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $database = $this->option('database');

        if (! in_array($database, ['mysql', 'mongodb'], true)) {
            $this->components->error('The --database option must be either mysql or mongodb.');

            return self::FAILURE;
        }

        if ($database === 'mongodb' && ! class_exists('MongoDB\\Laravel\\Eloquent\\Model')) {
            $this->components->error('MongoDB support is not installed. Run [composer require mongodb/laravel-mongodb] first.');

            return self::FAILURE;
        }

        $moduleOption = $this->option('module');

        if (! is_string($moduleOption) || trim($moduleOption) === '') {
            $this->components->error('The --module option is required, e.g. --module=People.');

            return self::FAILURE;
        }

        $names = $this->resolveNames($moduleOption);
        $moduleIsNew = ! File::isDirectory(base_path("modules/{$names['module']}"));

        $targets = $this->targetPaths($names, $moduleIsNew, $database);

        if (! $this->option('force')) {
            $conflicts = array_values(array_filter($targets, fn (string $path): bool => File::exists($path) || ($database === 'mysql' && $this->migrationExists($names))));

            if ($conflicts !== []) {
                $this->components->error('The following files already exist. Use --force to overwrite them:');

                foreach ($conflicts as $conflict) {
                    $this->line("  - {$conflict}");
                }

                return self::FAILURE;
            }
        }

        $createdFiles = $this->generateEntityFiles($names, $database);

        $composerChanged = false;
        $providersChanged = false;

        if ($moduleIsNew) {
            $createdFiles[] = $this->writeStub('service-provider', "modules/{$names['module']}/src/{$names['module']}ServiceProvider.php", $names);
            $createdFiles[] = $this->writeStub('routes', "modules/{$names['module']}/routes/web.php", $names);

            $composerChanged = $this->updateComposerAutoload($names['module']);

            if ($composerChanged) {
                $this->components->task('Running composer dump-autoload', fn (): bool => $this->runProcess(['composer', 'dump-autoload']));
            }

            $providersChanged = $this->updateBootstrapProviders($names['module']);
        } else {
            $this->appendRouteToExistingModule($names);
        }

        if ($this->getApplication()?->has('wayfinder:generate')) {
            $this->components->task('Generating Wayfinder routes', fn (): bool => $this->call('wayfinder:generate', [
                '--with-form' => true,
                '--no-interaction' => true,
            ]) === self::SUCCESS);
        } else {
            $this->components->warn('Wayfinder is not installed; generated route helpers were not refreshed.');
        }

        $phpFiles = array_values(array_filter($createdFiles, fn (string $path): bool => str_ends_with($path, '.php')));

        if ($phpFiles !== [] && File::exists(base_path('vendor/bin/pint'))) {
            $this->runProcess(['vendor/bin/pint', '--dirty', '--format=agent']);
        }

        $this->printSummary($names, $createdFiles, $composerChanged, $providersChanged, $database);

        return self::SUCCESS;
    }

    /**
     * @return array{module: string, entity: string, entityVariable: string, entityPlural: string, pluralVariable: string, table: string, resource: string, title: string, description: string, emptyLabel: string}
     */
    private function resolveNames(string $moduleOption): array
    {
        $entity = Str::studly((string) $this->argument('name'));
        $module = Str::studly($moduleOption);
        $entityPlural = Str::plural($entity);

        $table = $this->option('table');
        $table = is_string($table) && trim($table) !== '' ? $table : Str::snake($entityPlural);

        $resource = Str::plural(Str::kebab($entity));
        $entityVariable = Str::camel($entity);
        $pluralVariable = Str::camel($entityPlural);
        $entityPluralLower = Str::lower($entityPlural);

        return [
            'module' => $module,
            'entity' => $entity,
            'entityVariable' => $entityVariable,
            'entityPlural' => $entityPlural,
            'pluralVariable' => $pluralVariable,
            'table' => $table,
            'resource' => $resource,
            'title' => $entityPlural,
            'description' => "Manage application {$entityPluralLower}.",
            'emptyLabel' => "No {$entityPluralLower} found.",
        ];
    }

    /**
     * @param  array<string, string>  $names
     * @return list<string>
     */
    private function targetPaths(array $names, bool $moduleIsNew, string $database): array
    {
        $module = $names['module'];
        $entity = $names['entity'];
        $resource = $names['resource'];

        $targets = [
            base_path("modules/{$module}/src/Models/{$entity}.php"),
            base_path("modules/{$module}/src/Crud/{$entity}CrudDefinition.php"),
            base_path("modules/{$module}/src/Http/Controllers/{$entity}Controller.php"),
            base_path("modules/{$module}/src/Policies/{$entity}Policy.php"),
            base_path("database/factories/{$entity}Factory.php"),
            base_path("tests/Feature/{$module}/Crud/{$entity}CrudDefinitionTest.php"),
            base_path("resources/js/pages/{$resource}/Index.vue"),
        ];

        if ($moduleIsNew) {
            $targets[] = base_path("modules/{$module}/src/{$module}ServiceProvider.php");
            $targets[] = base_path("modules/{$module}/routes/web.php");
        }

        return $targets;
    }

    /**
     * @param  array<string, string>  $names
     */
    private function migrationExists(array $names): bool
    {
        return File::glob(base_path("modules/{$names['module']}/database/migrations/*_create_{$names['table']}_table.php")) !== [];
    }

    /**
     * @param  array<string, string>  $names
     * @return list<string>
     */
    private function generateEntityFiles(array $names, string $database): array
    {
        $module = $names['module'];
        $entity = $names['entity'];
        $resource = $names['resource'];

        $files = [
            $this->writeStub($database === 'mongodb' ? 'model-mongodb' : 'model', "modules/{$module}/src/Models/{$entity}.php", $names),
            $this->writeStub('crud-definition', "modules/{$module}/src/Crud/{$entity}CrudDefinition.php", $names),
            $this->writeStub('controller', "modules/{$module}/src/Http/Controllers/{$entity}Controller.php", $names),
            $this->writeStub('policy', "modules/{$module}/src/Policies/{$entity}Policy.php", $names),
            $this->writeStub('factory', "database/factories/{$entity}Factory.php", $names),
            $this->writeStub('test', "tests/Feature/{$module}/Crud/{$entity}CrudDefinitionTest.php", $names),
            $this->writeStub('vue-index', "resources/js/pages/{$resource}/Index.vue", $names),
        ];

        if ($database === 'mysql') {
            $existingMigrations = File::glob(base_path("modules/{$module}/database/migrations/*_create_{$names['table']}_table.php"));
            $migrationPath = $existingMigrations !== []
                ? $existingMigrations[0]
                : base_path('modules/'.$module.'/database/migrations/'.date('Y_m_d_His')."_create_{$names['table']}_table.php");

            array_unshift($files, $this->writeStub('migration', Str::after($migrationPath, base_path().'/'), $names));
        }

        return $files;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function writeStub(string $stubName, string $relativePath, array $replacements): string
    {
        $contents = $this->buildStub($stubName, $replacements);
        $path = base_path($relativePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);

        return $path;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function buildStub(string $stubName, array $replacements): string
    {
        $stub = File::get(dirname(__DIR__, 3)."/stubs/crud/{$stubName}.stub");

        $search = array_map(fn (string $key): string => '{{ '.$key.' }}', array_keys($replacements));

        return str_replace($search, array_values($replacements), $stub);
    }

    private function updateComposerAutoload(string $module): bool
    {
        $path = base_path('composer.json');
        $contents = File::get($path);

        try {
            /** @var array{autoload?: array{psr-4?: array<string, string>}} $composer */
            $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not parse composer.json: '.$exception->getMessage(), previous: $exception);
        }

        $namespace = "Modules\\{$module}\\";

        if (isset($composer['autoload']['psr-4'][$namespace])) {
            return false;
        }

        if (! isset($composer['autoload']['psr-4'])) {
            throw new RuntimeException('Could not find the psr-4 autoload section in composer.json.');
        }

        $composer['autoload']['psr-4'][$namespace] = "modules/{$module}/src/";

        $updated = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

        File::put($path, $updated);

        return true;
    }

    private function updateBootstrapProviders(string $module): bool
    {
        $path = base_path('bootstrap/providers.php');
        $contents = File::get($path);

        $useLine = "use Modules\\{$module}\\{$module}ServiceProvider;";
        $classLine = "    {$module}ServiceProvider::class,";

        $needsUse = ! str_contains($contents, $useLine);
        $needsClass = ! str_contains($contents, trim($classLine));

        if (! $needsUse && ! $needsClass) {
            return false;
        }

        $lines = preg_split('/\R/', $contents);

        if ($lines === false) {
            throw new RuntimeException('Could not parse bootstrap/providers.php.');
        }

        $lastUseIndex = null;
        $closingIndex = null;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ')) {
                $lastUseIndex = $index;
            }

            if (trim($line) === '];') {
                $closingIndex = $index;
            }
        }

        if ($lastUseIndex === null || $closingIndex === null) {
            throw new RuntimeException('Could not locate the expected structure in bootstrap/providers.php.');
        }

        if ($needsUse) {
            array_splice($lines, $lastUseIndex + 1, 0, [$useLine]);
            $closingIndex++;
        }

        if ($needsClass) {
            array_splice($lines, $closingIndex, 0, [$classLine]);
        }

        File::put($path, implode("\n", $lines));

        return true;
    }

    /**
     * @param  array<string, string>  $names
     */
    private function appendRouteToExistingModule(array $names): void
    {
        $module = $names['module'];
        $entity = $names['entity'];
        $resource = $names['resource'];

        $path = base_path("modules/{$module}/routes/web.php");

        if (! File::exists($path)) {
            throw new RuntimeException("Expected an existing routes file at modules/{$module}/routes/web.php but it was not found.");
        }

        $contents = File::get($path);

        $useLine = "use Modules\\{$module}\\Http\\Controllers\\{$entity}Controller;";
        $resourceLine = "    Route::resource('{$resource}', {$entity}Controller::class)->only(['index', 'store', 'update', 'destroy']);";

        if (str_contains($contents, $resourceLine)) {
            return;
        }

        if (! str_contains($contents, $useLine)) {
            $lines = preg_split('/\R/', $contents);

            if ($lines === false) {
                throw new RuntimeException("Could not parse modules/{$module}/routes/web.php.");
            }

            $lastUseIndex = null;

            foreach ($lines as $index => $line) {
                if (str_starts_with(trim($line), 'use ')) {
                    $lastUseIndex = $index;
                }
            }

            if ($lastUseIndex === null) {
                throw new RuntimeException("Could not locate a use statement to anchor the new import in modules/{$module}/routes/web.php.");
            }

            array_splice($lines, $lastUseIndex + 1, 0, [$useLine]);
            $contents = implode("\n", $lines);
        }

        $closingPosition = strrpos($contents, '});');

        if ($closingPosition === false) {
            throw new RuntimeException("Could not locate the route group closing brace in modules/{$module}/routes/web.php.");
        }

        $updated = substr($contents, 0, $closingPosition).$resourceLine."\n".substr($contents, $closingPosition);

        File::put($path, $updated);
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command): bool
    {
        $result = Process::path(base_path())->timeout(120)->run($command);

        if ($result->failed()) {
            $this->components->warn('Command ['.implode(' ', $command).'] failed: '.$result->errorOutput());
        }

        return $result->successful();
    }

    /**
     * @param  array<string, string>  $names
     * @param  list<string>  $createdFiles
     */
    private function printSummary(array $names, array $createdFiles, bool $composerChanged, bool $providersChanged, string $database): void
    {
        $this->newLine();
        $this->components->info('Files created:');

        foreach ($createdFiles as $file) {
            $this->line('  - '.Str::after($file, base_path().'/'));
        }

        if ($composerChanged) {
            $this->newLine();
            $this->line("Inserted into composer.json: \"Modules\\\\{$names['module']}\\\\\": \"modules/{$names['module']}/src/\",");
        }

        if ($providersChanged) {
            $this->line("Inserted into bootstrap/providers.php: use Modules\\{$names['module']}\\{$names['module']}ServiceProvider; and {$names['module']}ServiceProvider::class,");
        }

        $this->newLine();
        $this->components->info('Next steps:');
        $this->line($database === 'mongodb'
            ? '1. Configure the generated model collection and indexes for your MongoDB schema.'
            : '1. php artisan migrate');
        $this->line("2. Add a nav entry to BOTH resources/js/components/AppSidebar.vue and resources/js/components/AppHeader.vue's mainNavItems array (no central nav registry exists in this codebase) — e.g.:");
        $this->line("   import { index as {$names['resource']}Index } from '@/routes/{$names['resource']}';");
        $this->line("   { title: '{$names['title']}', href: {$names['resource']}Index(), icon: /* pick a @lucide/vue icon */ }");
        $this->line('3. Replace the placeholder `name` column with real fields across: the migration, #[Fillable] on the Model, columns()/fields() on the CrudDefinition, the Factory\'s definition(), and Index.vue\'s slots.');
        $this->line("4. Review authorization rules in {$names['entity']}Policy.php (currently `return true` for every ability).");
    }
}
