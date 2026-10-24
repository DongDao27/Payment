<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CustomerResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordRecoveryService
{
    public function requestLink(string $email): void
    {
        // The broker timebox, response and throttle do not disclose account existence.
        Password::broker()->sendResetLink(['email' => $email, 'role' => 'customer'],
            fn (User $user, string $token) => $user->notify(new CustomerResetPassword($token)));
    }

    public function reset(array $input): void
    {
        $status = DB::transaction(function () use ($input) {
            // Serialize consumption for the account; validate and delete the token within this transaction.
            User::where('email', $input['email'])->where('role', 'customer')->lockForUpdate()->first();

            return Password::broker()->reset($input + ['role' => 'customer'], function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                DB::afterCommit(fn () => event(new PasswordReset($user)));
            });
        }, 3);

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => 'Liên kết không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.']);
        }
    }
}
