<?php

namespace App\Http\Requests;

use App\Actions\Auth\NormalizeIdentity;

class SubscribeNewsletterRequest extends StrictFormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => NormalizeIdentity::email($this->email)]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'consent' => ['required', 'accepted'],
        ];
    }
}
