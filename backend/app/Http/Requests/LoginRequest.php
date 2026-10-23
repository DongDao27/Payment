<?php

namespace App\Http\Requests;

class LoginRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['identifier' => ['required', 'string', 'max:254'], 'password' => ['required', 'string', 'max:128']];
    }
}
