<?php

namespace Modules\Crud;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Modules\Crud\Console\Commands\InstallCrudCommand;
use Modules\Crud\Console\Commands\InstallShellCommand;
use Modules\Crud\Console\Commands\MakeCrudCommand;
use Modules\Crud\Console\Commands\UpgradeCrudRoutesCommand;

class CrudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/crud.php', 'crud');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__).'/config/crud.php' => config_path('crud.php'),
        ], 'crud-config');

        $this->loadJsonTranslationsFrom(dirname(__DIR__).'/resources/lang');

        if (class_exists('Inertia\\Inertia')) {
            $translations = fn (): array => app(CrudTranslations::class)->all();

            if (method_exists(Inertia::getFacadeRoot(), 'shareOnce')) {
                Inertia::shareOnce('translations', $translations)
                    ->as('translations.'.$this->app->getLocale());
            } else {
                Inertia::share('translations', $translations);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCrudCommand::class,
                InstallShellCommand::class,
                MakeCrudCommand::class,
                UpgradeCrudRoutesCommand::class,
            ]);
        }

        Route::macro('crudResource', function (string $resource, string $controller, string $model): void {
            app(CrudRouteRegistrar::class)->register($resource, $controller, $model);
        });
    }

    /**
     * @return array<string, string>
     */
}
