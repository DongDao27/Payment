<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class RegisterCustomer
{
    public function handle(array $data): User
    {
        try {
            return User::create(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'password'])));
        } catch (UniqueConstraintViolationException) {
            // A concurrent normalized registration can win after validation.
            throw ValidationException::withMessages(['identifier' => 'Email or phone is already registered.']);
        }
    }
}
