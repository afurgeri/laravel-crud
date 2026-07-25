<?php

namespace Modules\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCrudCommand extends Command
{
    protected $signature = 'crud:install
        {--force : Overwrite existing frontend files}
        {--skip-existing : Install only frontend files that are missing}';

    protected $description = 'Install the generic CRUD frontend components and types';

    public function handle(): int
    {
        $sourceRoot = dirname(__DIR__, 3).'/resources/js';
        $files = File::allFiles($sourceRoot);
        $targets = [];

        foreach ($files as $file) {
            $relative = str_replace($sourceRoot.'/', '', $file->getPathname());
            $targets[] = [
                'source' => $file->getPathname(),
                'target' => base_path('resources/js/'.$relative),
            ];
        }

        $conflicts = array_values(array_filter(
            $targets,
            fn (array $file): bool => File::exists($file['target']),
        ));

        if ($conflicts !== [] && ! $this->option('force') && ! $this->option('skip-existing')) {
            $this->components->error('CRUD frontend files already exist. Use --force to overwrite them:');

            foreach ($conflicts as $file) {
                $this->line('  - '.$file['target']);
            }

            return self::FAILURE;
        }

        if ($this->option('skip-existing')) {
            $targets = array_values(array_filter(
                $targets,
                fn (array $file): bool => ! File::exists($file['target']),
            ));
        }

        foreach ($targets as $file) {
            File::ensureDirectoryExists(dirname($file['target']));
            File::copy($file['source'], $file['target']);
        }

        $translationPath = base_path('lang/en.json');
        $translations = json_decode(
            File::get(dirname(__DIR__, 3).'/resources/lang/en.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $existingTranslations = File::exists($translationPath)
            ? json_decode(File::get($translationPath), true, 512, JSON_THROW_ON_ERROR)
            : [];

        File::ensureDirectoryExists(dirname($translationPath));
        File::put(
            $translationPath,
            json_encode(
                [...$translations, ...$existingTranslations],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ).PHP_EOL,
        );

        $this->components->info('CRUD frontend components installed.');
        $this->line('CRUD translation infrastructure installed in lang/en.json.');
        $this->line('Run npm run build or npm run dev to compile the frontend.');

        return self::SUCCESS;
    }
}
