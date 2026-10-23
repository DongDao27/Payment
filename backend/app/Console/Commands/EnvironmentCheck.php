<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnvironmentCheck extends Command
{
    protected $signature = 'app:environment-check';

    protected $description = 'Check backend runtime and database without displaying credentials';

    public function handle(): int
    {
        if (! extension_loaded('pdo_mysql') || config('database.default') !== 'mysql') {
            $this->error('MySQL and pdo_mysql are required.');

            return self::FAILURE;
        }

        try {
            $database = DB::selectOne('SELECT VERSION() AS version, DATABASE() AS name, @@session.time_zone AS timezone');
        } catch (\Throwable) {
            $this->error('Database connection failed. Check private env configuration.');

            return self::FAILURE;
        }

        if (str_contains($database->version, 'MariaDB') || version_compare($database->version, '8.0.16', '<')) {
            $this->error('MySQL >= 8.0.16 is required for enforced CHECK constraints.');

            return self::FAILURE;
        }

        $this->info('PHP '.PHP_VERSION.'; Laravel '.app()->version().'; MySQL '.$database->version);
        $this->info('Database '.$database->name.'; connection timezone '.$database->timezone);

        return self::SUCCESS;
    }
}
