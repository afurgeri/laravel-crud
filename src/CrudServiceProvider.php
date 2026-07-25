<?php

namespace Modules\Crud;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Modules\Crud\Console\Commands\InstallCrudCommand;
use Modules\Crud\Console\Commands\MakeCrudCommand;

class CrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(dirname(__DIR__).'/resources/lang');

        if (class_exists('Inertia\\Inertia')) {
            Inertia::share('translations', fn (): array => $this->translations());
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCrudCommand::class,
                MakeCrudCommand::class,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function translations(): array
    {
        $translations = [];
        $locales = array_unique([
            (string) config('app.fallback_locale'),
            (string) config('app.locale'),
        ]);

        foreach ([dirname(__DIR__).'/resources/lang', lang_path()] as $directory) {
            foreach ($locales as $locale) {
                $path = "{$directory}/{$locale}.json";

                if (! File::exists($path)) {
                    continue;
                }

                /** @var array<string, string> $localeTranslations */
                $localeTranslations = json_decode(
                    File::get($path),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                $translations = [...$translations, ...$localeTranslations];
            }
        }

        return $translations;
    }
}
