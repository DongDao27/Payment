<?php

namespace App\Console\Commands;

use App\Actions\Auth\NormalizeIdentity;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--name= : Display name}';

    protected $description = 'Create the single bookstore admin; never reset an existing account';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->info('Admin already exists; no credentials changed.');

            return self::SUCCESS;
        }

        $data = [
            'name' => $this->option('name') ?: ($this->input->isInteractive() ? $this->ask('Name') : null),
            'email' => NormalizeIdentity::email(config('admin.email') ?: ($this->input->isInteractive() ? $this->ask('Email (optional if phone provided)') : null)),
            'phone' => NormalizeIdentity::phone(config('admin.phone') ?: ($this->input->isInteractive() ? $this->ask('Vietnam phone (optional if email provided)') : null)),
            'password' => config('admin.password') ?: ($this->input->isInteractive() ? $this->secret('Password (12–128 characters)') : null),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:254', 'unique:users,email'],
            'phone' => ['nullable', 'required_without:email', 'regex:/^\+84[35789][0-9]{8}$/D', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:12', 'max:128'],
        ]);
        if ($validator->fails()) {
            $this->error('Admin input invalid. Supply a valid name, unique email/phone and 12–128 character secret.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($data) {
                $user = new User($data);
                $user->role = 'admin';
                $user->save();
            });
        } catch (UniqueConstraintViolationException) {
            $this->error('Admin or identity already exists; no credentials changed.');

            return self::FAILURE;
        }

        $this->info('Admin created. No password displayed.');

        return self::SUCCESS;
    }
}
