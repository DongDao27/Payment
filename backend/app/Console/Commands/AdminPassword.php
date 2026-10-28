<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminPassword extends Command
{
    protected $signature = 'admin:password';

    protected $description = 'Interactively replace the local admin password and revoke sessions; development only';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) || ! $this->input->isInteractive()) {
            $this->error('This command requires an interactive local development terminal.');

            return self::FAILURE;
        }
        $admin = User::where('role', 'admin')->first();
        if (! $admin || ! $this->confirm('Replace the existing admin password and sign out every admin session?', false)) {
            $this->info('No credentials changed.');

            return self::FAILURE;
        }
        $password = $this->secret('New password (12–128 characters)');
        $confirmation = $this->secret('Confirm new password');
        if (Validator::make(['password' => $password, 'password_confirmation' => $confirmation], ['password' => ['required', 'string', 'min:12', 'max:128', 'confirmed']])->fails()) {
            $this->error('Passwords must match and contain 12–128 characters. No credentials changed.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($admin, $password) {
            $locked = User::whereKey($admin->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $locked->id)->delete();
        }, 3);
        $this->info('Password replaced. No password displayed. Log in again.');

        return self::SUCCESS;
    }
}
