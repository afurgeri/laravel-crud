<?php

namespace Modules\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCrudCommand extends Command
{
    protected $signature = 'crud:install {--force : Overwrite existing frontend files}';

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

        if ($conflicts !== [] && ! $this->option('force')) {
            $this->components->error('CRUD frontend files already exist. Use --force to overwrite them:');

            foreach ($conflicts as $file) {
                $this->line('  - '.$file['target']);
            }

            return self::FAILURE;
        }

        foreach ($targets as $file) {
            File::ensureDirectoryExists(dirname($file['target']));
            File::copy($file['source'], $file['target']);
        }

        $this->components->info('CRUD frontend components installed.');
        $this->line('Run npm run build or npm run dev to compile the frontend.');

        return self::SUCCESS;
    }
}
