<?php

namespace App\Http\Requests;

class CreateOrderRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['quote_id' => ['required', 'uuid']];
    }

    public function after(): array
    {
        return [function ($validator) {
            $key = $this->header('Idempotency-Key');
            if (! is_string($key) || ! preg_match('/^[a-zA-Z0-9._-]{8,100}$/D', $key)) {
                $validator->errors()->add('Idempotency-Key', 'Provide an 8–100 character ASCII request key.');
            }
        }];
    }
}
