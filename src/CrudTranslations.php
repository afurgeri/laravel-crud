<?php

namespace Modules\Crud;

/**
 * The JSON translations sent to the frontend: the application's and those of every
 * package that registered its own, with the current locale over the fallback locale.
 */
final class CrudTranslations
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $locales = array_unique([
            (string) config('app.fallback_locale'),
            app()->getLocale(),
        ]);

        $loader = app('translator')->getLoader();
        $translations = [];

        foreach ($locales as $locale) {
            /** @var array<string, string> $localeTranslations */
            $localeTranslations = $loader->load($locale, '*', '*');
            $translations = [...$translations, ...$localeTranslations];
        }

        return $translations;
    }
}
