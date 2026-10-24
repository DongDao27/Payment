<?php

namespace App\Http\Requests;

class ResetPasswordRequest extends ForgotPasswordRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'token' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'password' => ['required', 'string', 'min:12', 'max:128', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'token.required' => 'Liên kết chưa đầy đủ. Vui lòng yêu cầu liên kết mới.',
            'token.regex' => 'Liên kết không hợp lệ. Vui lòng yêu cầu liên kết mới.',
            'password.min' => 'Mật khẩu cần ít nhất 12 ký tự.',
            'password.max' => 'Mật khẩu không vượt quá 128 ký tự.',
            'password.confirmed' => 'Hai mật khẩu chưa khớp nhau.',
        ];
    }
}
