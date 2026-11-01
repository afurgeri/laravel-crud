<?php

namespace Modules\Crud\Console;

/**
 * Small, idempotent edits to application files for the application shell.
 * Each method returns the contents unchanged when the edit is already there,
 * and null when the file does not look like the starter kit.
 */
final class ShellPatcher
{
    private const BASE_STYLES = <<<'CSS'

@layer base {
    ::selection {
        @apply bg-primary/20 text-foreground;
    }

    /* iOS Safari gives date and time inputs an intrinsic width that ignores the column, and centers the value. */
    input[type='date'],
    input[type='time'] {
        -webkit-appearance: none;
        appearance: none;
    }

    input::-webkit-date-and-time-value {
        text-align: left;
        min-height: 1.25rem;
    }
}

CSS;

    /**
     * Adds the item permission and the menu group to the navigation types.
     */
    public function navigationTypes(string $contents): ?string
    {
        if (! str_contains($contents, 'export type NavItem = {')) {
            return null;
        }

        if (! preg_match('/export type NavItem = \{[^}]*\bpermission\?: string;/s', $contents)) {
            $contents = preg_replace('/(export type NavItem = \{[^}]*?)(\n\};)/s', "$1\n    permission?: string;$2", $contents, 1) ?? $contents;
        }

        if (! str_contains($contents, 'export type NavGroup')) {
            $contents = rtrim($contents)."\n\nexport type NavGroup = {\n    title: string;\n    items: NavItem[];\n};\n";
        }

        return $contents;
    }

    public function appStyles(string $contents): string
    {
        if (str_contains($contents, 'input::-webkit-date-and-time-value')) {
            return $contents;
        }

        return rtrim($contents)."\n".self::BASE_STYLES;
    }

    /**
     * Keeps the test suite in English whatever locale the application uses.
     */
    public function phpunitLocale(string $contents): ?string
    {
        if (str_contains($contents, 'name="APP_LOCALE"')) {
            return $contents;
        }

        if (preg_match('/^(\s*)<env name="APP_ENV" value="testing"\/>\n/m', $contents, $match) !== 1) {
            return null;
        }

        return str_replace($match[0], $match[0].$match[1].'<env name="APP_LOCALE" value="en" force="true"/>'."\n", $contents);
    }

    public function environmentLocale(string $contents, string $locale): string
    {
        foreach (['APP_LOCALE', 'APP_FALLBACK_LOCALE'] as $key) {
            $line = "{$key}={$locale}";

            $contents = preg_match("/^{$key}=.*$/m", $contents) === 1
                ? (preg_replace("/^{$key}=.*$/m", $line, $contents) ?? $contents)
                : rtrim($contents)."\n{$line}\n";
        }

        return $contents;
    }
}
