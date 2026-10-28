<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function update(int $userId, array $data): User
    {
        try {
            return DB::transaction(function () use ($userId, $data) {
                $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
                $user->fill(collect($data)->only(['name', 'email', 'phone'])->all());
                if ($user->isDirty(['email', 'phone']) && ! Hash::check($data['current_password'] ?? '', $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Nhập đúng mật khẩu hiện tại để đổi email hoặc số điện thoại.']);
                }
                if ($user->isDirty('email')) {
                    DB::table('password_reset_tokens')->where('email', $user->getOriginal('email'))->delete();
                    $user->email_verified_at = null;
                }
                $user->save();

                return $user->refresh();
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Email hoặc số điện thoại vừa được sử dụng. Vui lòng kiểm tra lại.']);
        }
    }
}
