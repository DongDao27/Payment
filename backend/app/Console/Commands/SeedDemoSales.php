<?php

namespace App\Console\Commands;

use App\Services\DemoSalesSeeder;
use Illuminate\Console\Command;

class SeedDemoSales extends Command
{
    protected $signature = 'demo:seed-sales {--orders=1000} {--seed=20261023}';

    protected $description = 'Generate isolated, synthetic sales only in local demo/test databases; never production';

    public function handle(DemoSalesSeeder $seeder): int
    {
        $values = [$this->option('orders'), $this->option('seed')];
        if (array_any($values, fn ($value) => filter_var($value, FILTER_VALIDATE_INT) === false)) {
            $this->error('Orders and seed must be integers.');

            return self::FAILURE;
        }
        try {
            $result = $seeder->run((int) $values[0], (int) $values[1]);
        } catch (\RuntimeException $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
        $this->line(json_encode($result, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
