<?php

namespace App\Http\Requests;

use App\Actions\Auth\NormalizeIdentity;

class RegisterRequest extends StrictFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? NormalizeIdentity::email($this->email) : $this->email,
            'phone' => is_string($this->phone) ? NormalizeIdentity::phone($this->phone) : $this->phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:254', 'unique:users,email'],
            'phone' => ['nullable', 'required_without:email', 'regex:/^\+84[35789][0-9]{8}$/D', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:12', 'max:128', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
