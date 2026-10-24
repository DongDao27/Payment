<?php

namespace App\Http\Requests;

use App\Actions\Auth\NormalizeIdentity;

class ForgotPasswordRequest extends StrictFormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => NormalizeIdentity::email($this->email)]);
        }
    }

    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:254']];
    }

    public function messages(): array
    {
        return ['email.required' => 'Vui lòng nhập email đăng ký.', 'email.email' => 'Vui lòng nhập email hợp lệ.'];
    }
}
