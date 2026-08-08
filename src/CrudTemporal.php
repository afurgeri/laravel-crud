<?php

namespace Modules\Crud;

use Carbon\CarbonImmutable;

final class CrudTemporal
{
    public const DISPLAY_TIMEZONE = 'America/Argentina/Buenos_Aires';

    public static function displayTimezone(): string
    {
        return app()->bound('config')
            ? (string) config('crud.timezone', self::DISPLAY_TIMEZONE)
            : self::DISPLAY_TIMEZONE;
    }

    public static function normalizeDateTime(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return self::utcDateTime($value)->format('Y-m-d H:i:s');
    }

    public static function utcDateTime(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, self::displayTimezone())->utc();
    }
}
