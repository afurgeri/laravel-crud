<?php

namespace Modules\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use RuntimeException;

class UpgradeCrudRoutesCommand extends Command
{
    protected $signature = 'crud:upgrade-routes
        {resource? : Limit the upgrade to one CRUD resource}
        {--model= : The model class when it cannot be inferred from the controller}
        {--controller= : The controller class when it cannot be inferred from the route}
        {--dry-run : Show the routes that would be added without writing files}';

    protected $description = 'Add generic CRUD option routes to existing resource routes';

    /**
     * @var class-string
     */
    private const OPTIONS_CONTROLLER = 'Modules\\Crud\\Http\\Controllers\\CrudOptionsController';

    public function handle(): int
    {
        $resource = $this->argument('resource');
        $resource = is_string($resource) && trim($resource) !== '' ? trim($resource) : null;
        $modelOption = $this->option('model');
        $modelOption = is_string($modelOption) && trim($modelOption) !== ''
            ? ltrim(trim($modelOption), '\\')
            : null;
        $controllerOption = $this->option('controller');
        $controllerOption = is_string($controllerOption) && trim($controllerOption) !== ''
            ? ltrim(trim($controllerOption), '\\')
            : null;

        $upgrades = [];

        foreach ($this->routeFiles() as $path) {
            foreach ($this->resourceRoutes($path, $resource) as $route) {
                $controller = $controllerOption ?? $this->resolveController($path, $route['controller']);
                $model = $modelOption ?? $this->resolveModel($controller);

                if ($model === null) {
                    $this->components->error("Could not infer the model for [{$route['resource']}]. Pass --model=... to continue.");

                    return self::FAILURE;
                }

                $contents = File::get($path);

                if ($this->hasOptionsRoute($contents, $route['resource'])) {
                    continue;
                }

                $upgrades[] = [
                    'path' => $path,
                    'resource' => $route['resource'],
                    'line' => $route['line'],
                    'model' => $model,
                ];
            }
        }

        if ($upgrades === []) {
            $this->components->info('No CRUD routes require upgrading.');

            return self::SUCCESS;
        }

        foreach ($upgrades as $upgrade) {
            $this->line(sprintf(
                '%s %s -> %s.options',
                $this->option('dry-run') ? 'Would add' : 'Adding',
                $upgrade['path'],
                $upgrade['resource'],
            ));
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        foreach ($upgrades as $upgrade) {
            $this->upgradeFile(
                $upgrade['path'],
                $upgrade['resource'],
                $upgrade['line'],
                $upgrade['model'],
            );
        }

        $this->components->info('CRUD option routes upgraded.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function routeFiles(): array
    {
        return array_values(array_unique([
            ...File::glob(base_path('routes/*.php')),
            ...File::glob(base_path('modules/*/routes/*.php')),
        ]));
    }

    /**
     * @return list<array{resource: string, controller: string, line: int}>
     */
    private function resourceRoutes(string $path, ?string $resource): array
    {
        $lines = preg_split('/\R/', File::get($path));

        if ($lines === false) {
            return [];
        }

        $routes = [];

        foreach ($lines as $lineNumber => $line) {
            if (! preg_match(
                "/Route::resource\\(\\s*['\"]([^'\"]+)['\"]\\s*,\\s*([A-Za-z_][A-Za-z0-9_]*)::class/",
                $line,
                $matches,
            )) {
                continue;
            }

            if ($resource !== null && $matches[1] !== $resource) {
                continue;
            }

            $routes[] = [
                'resource' => $matches[1],
                'controller' => $matches[2],
                'line' => $lineNumber,
            ];
        }

        return $routes;
    }

    private function resolveController(string $routePath, string $shortName): ?string
    {
        $contents = File::get($routePath);
        preg_match_all('/use\s+([^;\r\n]+);/', $contents, $matches);

        foreach ($matches[1] as $import) {
            if (str_ends_with($import, "\\{$shortName}")) {
                return $import;
            }
        }

        return null;
    }

    private function resolveModel(?string $controller): ?string
    {
        if ($controller === null || ! class_exists($controller)) {
            return null;
        }

        $reflection = new ReflectionClass($controller);
        $path = $reflection->getFileName();

        if ($path === false) {
            return null;
        }

        $contents = File::get($path);

        if (preg_match('/\b([A-Z][A-Za-z0-9_]*)::makeCrudDefinition\(\)/', $contents, $modelMatch) !== 1) {
            return null;
        }

        preg_match_all('/use\s+([^;\r\n]+);/', $contents, $matches);

        foreach ($matches[1] as $import) {
            if (str_ends_with($import, "\\{$modelMatch[1]}")) {
                return $import;
            }
        }

        return null;
    }

    private function hasOptionsRoute(string $contents, string $resource): bool
    {
        return str_contains($contents, "{$resource}/options/{filter}")
            || str_contains($contents, "->name('{$resource}.options')")
            || str_contains($contents, "->name(\"{$resource}.options\")");
    }

    private function upgradeFile(string $path, string $resource, int $lineNumber, string $model): void
    {
        $lines = preg_split('/\R/', File::get($path));

        if ($lines === false) {
            throw new RuntimeException("Could not parse {$path}.");
        }

        $lastUseIndex = null;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ')) {
                $lastUseIndex = $index;
            }
        }

        if ($lastUseIndex === null) {
            throw new RuntimeException("Could not locate a use statement in {$path}.");
        }

        if (! str_contains(implode("\n", $lines), 'use '.self::OPTIONS_CONTROLLER.';')) {
            array_splice($lines, $lastUseIndex + 1, 0, ['use '.self::OPTIONS_CONTROLLER.';']);

            if ($lastUseIndex < $lineNumber) {
                $lineNumber++;
            }
        }

        $indent = preg_match('/^\s*/', $lines[$lineNumber], $indentMatch) === 1
            ? $indentMatch[0]
            : '';
        $route = [
            "{$indent}Route::get('{$resource}/options/{filter}', CrudOptionsController::class)",
            "{$indent}    ->name('{$resource}.options')",
            "{$indent}    ->defaults('model', \\{$model}::class);",
        ];

        array_splice($lines, $lineNumber, 0, $route);
        File::put($path, implode("\n", $lines));
    }
}
