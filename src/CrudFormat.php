<?php

namespace Modules\Crud;

final class CrudFormat
{
    /**
     * Locale used by the frontend to format dates and amounts, e.g. "es-AR".
     */
    public static function displayLocale(): string
    {
        if (! app()->bound('config')) {
            return 'en';
        }

        $locale = config('crud.locale') ?: app()->getLocale();

        return str_replace('_', '-', (string) $locale);
    }
}
