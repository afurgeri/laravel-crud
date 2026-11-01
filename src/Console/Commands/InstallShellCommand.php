<?php

namespace Modules\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Crud\Console\ShellPatcher;

class InstallShellCommand extends Command
{
    protected $signature = 'crud:install-shell
        {--force : Overwrite shell components that differ from the package version}
        {--locale= : Also set up this application locale (supported: es)}';

    protected $description = 'Install the application shell: sidebar, permission-aware navigation and translations';

    private const SIDEBAR = 'components/AppSidebar.vue';

    /**
     * @var list<string>
     */
    private array $manual = [];

    public function handle(): int
    {
        $locale = $this->option('locale');
        $shellRoot = dirname(__DIR__, 3).'/resources/shell';

        if (is_string($locale) && $locale !== '' && ! File::isDirectory("{$shellRoot}/lang/{$locale}")) {
            $this->components->error("The [{$locale}] locale is not supported. Supported locales: es.");

            return self::FAILURE;
        }

        $hasApplicationMenu = $this->hasApplicationMenu($shellRoot);

        $this->installComponents($shellRoot, $hasApplicationMenu);
        $this->installNavigation($shellRoot);

        $patcher = new ShellPatcher;

        $this->patch('resources/js/types/navigation.ts', fn (string $contents): ?string => $patcher->navigationTypes($contents), 'Add permission?: string to NavItem and export a NavGroup type.');
        $this->patch('resources/css/app.css', fn (string $contents): string => $patcher->appStyles($contents), 'Add the shell base styles to resources/css/app.css.');

        if (is_string($locale) && $locale !== '') {
            $this->installLocale($shellRoot, $locale, $patcher);
        }

        $this->newLine();

        foreach ($this->manual as $instruction) {
            $this->components->warn($instruction);
        }

        $this->line('Run npm run build or npm run dev to compile the frontend.');

        return self::SUCCESS;
    }

    private function installComponents(string $shellRoot, bool $hasApplicationMenu): void
    {
        foreach (File::allFiles("{$shellRoot}/js") as $file) {
            $relative = str_replace("{$shellRoot}/js/", '', $file->getPathname());
            $target = base_path("resources/js/{$relative}");

            if (! File::exists($target)) {
                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
                $this->report($relative, '<fg=green>CREATED</>');

                continue;
            }

            if (File::get($target) === File::get($file->getPathname())) {
                $this->report($relative, '<fg=gray>UP TO DATE</>');

                continue;
            }

            if ($relative === self::SIDEBAR && ! str_contains(File::get($target), "from '@/navigation'") && ! ($hasApplicationMenu && $this->option('force'))) {
                $this->report($relative, '<fg=yellow>MANUAL</>');
                $this->manual[] = 'Move the menu groups of resources/js/components/AppSidebar.vue into resources/js/navigation.ts, then run this command again with --force.';

                continue;
            }

            if (! $this->option('force')) {
                $this->report($relative, '<fg=yellow>CHANGED, KEPT</>');

                continue;
            }

            File::copy($file->getPathname(), $target);
            $this->report($relative, '<fg=green>UPDATED</>');
        }
    }

    /**
     * Whether the application already moved its menu into resources/js/navigation.ts,
     * so a sidebar that still declares its own menu may be replaced.
     */
    private function hasApplicationMenu(string $shellRoot): bool
    {
        $menu = base_path('resources/js/navigation.ts');

        return File::exists($menu) && File::get($menu) !== File::get("{$shellRoot}/stubs/navigation.ts");
    }

    /**
     * The menu belongs to the application, so it is only created when missing.
     */
    private function installNavigation(string $shellRoot): void
    {
        $target = base_path('resources/js/navigation.ts');

        if (File::exists($target)) {
            $this->report('navigation.ts', '<fg=gray>KEPT</>');

            return;
        }

        File::copy("{$shellRoot}/stubs/navigation.ts", $target);
        $this->report('navigation.ts', '<fg=green>CREATED</>');
    }

    private function installLocale(string $shellRoot, string $locale, ShellPatcher $patcher): void
    {
        foreach (File::files("{$shellRoot}/lang/{$locale}") as $file) {
            $target = lang_path("{$locale}/{$file->getFilename()}");

            if (File::exists($target)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
            $this->report("lang/{$locale}/{$file->getFilename()}", '<fg=green>CREATED</>');
        }

        foreach (['.env.example', '.env'] as $environmentFile) {
            if (File::exists(base_path($environmentFile))) {
                $this->patch($environmentFile, fn (string $contents): string => $patcher->environmentLocale($contents, $locale), "Set APP_LOCALE={$locale} in {$environmentFile}.");
            }
        }

        $this->patch('phpunit.xml', fn (string $contents): ?string => $patcher->phpunitLocale($contents), 'Force APP_LOCALE=en in phpunit.xml so tests do not depend on the application locale.');
    }

    /**
     * @param  callable(string): ?string  $patch
     */
    private function patch(string $relativePath, callable $patch, string $instruction): void
    {
        $path = base_path($relativePath);

        if (! File::exists($path)) {
            $this->report($relativePath, '<fg=yellow>MANUAL</>');
            $this->manual[] = $instruction;

            return;
        }

        $contents = File::get($path);
        $patched = $patch($contents);

        if ($patched === null) {
            $this->report($relativePath, '<fg=yellow>MANUAL</>');
            $this->manual[] = $instruction;
        } elseif ($patched === $contents) {
            $this->report($relativePath, '<fg=gray>UP TO DATE</>');
        } else {
            File::put($path, $patched);
            $this->report($relativePath, '<fg=green>UPDATED</>');
        }
    }

    private function report(string $path, string $status): void
    {
        $this->components->twoColumnDetail($path, $status);
    }
}
