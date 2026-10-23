<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AdminAccount extends Command
{
    protected $signature = 'admin:account';

    protected $description = 'Show the existing admin login identity locally without displaying secrets';

    public function handle(): int
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->warn('No admin exists. Run php artisan admin:create to create one.');

            return self::FAILURE;
        }
        $this->table(['Name', 'Email', 'Phone'], [[$admin->name, $admin->email ?? '-', $admin->phone ?? '-']]);
        $this->info('Open http://localhost:3000/admin and log in with this identity and your existing password.');

        return self::SUCCESS;
    }
}
