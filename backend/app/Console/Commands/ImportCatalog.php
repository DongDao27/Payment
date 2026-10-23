<?php

namespace App\Console\Commands;

use App\Services\CatalogImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportCatalog extends Command
{
    protected $signature = 'catalog:import {manifest : Validated staged catalog.json} {--apply : Insert new SKUs and local images; never overwrite existing records}';

    protected $description = 'Dry-run or apply an owner workbook import in local/testing only';

    public function handle(CatalogImportService $import): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('This local import command is not enabled in production.');

            return self::FAILURE;
        }
        $file = realpath($this->argument('manifest'));
        if (! $file || ! is_file($file)) {
            $this->error('Manifest file not found.');

            return self::FAILURE;
        }
        try {
            $this->line(json_encode($import->run($file, (bool) $this->option('apply')), JSON_THROW_ON_ERROR));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
