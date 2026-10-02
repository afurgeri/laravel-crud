<?php

namespace Modules\Crud;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class CrudTemporal
{
    public static function displayTimezone(): string
    {
        return app()->bound('config')
            ? (string) config('app.timezone', 'UTC')
            : 'UTC';
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

    public static function serializeDateTime(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $date = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value, 'UTC');

        return $date->setTimezone(self::displayTimezone())->toIso8601String();
    }
}
