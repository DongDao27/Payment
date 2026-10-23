<?php

namespace App\Http\Requests;

class PaymentAttemptRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (! preg_match('/^[a-zA-Z0-9._-]{8,100}$/D', (string) $this->header('Idempotency-Key'))) {
                $validator->errors()->add('Idempotency-Key', 'Provide an 8–100 character ASCII request key.');
            }
        }];
    }
}
