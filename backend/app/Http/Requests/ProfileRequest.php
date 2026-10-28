<?php

namespace App\Http\Requests;

use App\Actions\Auth\NormalizeIdentity;
use Illuminate\Validation\Rule;

class ProfileRequest extends StrictFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? NormalizeIdentity::email($this->email) : $this->email,
            'phone' => is_string($this->phone) ? NormalizeIdentity::phone($this->phone) : $this->phone,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:254', Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['nullable', 'required_without:email', 'regex:/^\+84[35789][0-9]{8}$/D', Rule::unique('users')->ignore($this->user()->id)],
            'current_password' => ['sometimes', 'nullable', 'string', 'max:128'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'email.required_without' => 'Vui lòng giữ ít nhất email hoặc số điện thoại.',
            'phone.required_without' => 'Vui lòng giữ ít nhất email hoặc số điện thoại.',
            'phone.regex' => 'Vui lòng nhập số điện thoại Việt Nam hợp lệ.',
        ];
    }
}
