<?php

namespace App\Http\Requests;

use App\Actions\Auth\NormalizeIdentity;
use Illuminate\Validation\Rule;

class QuoteRequest extends StrictFormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->recipient_phone)) {
            $this->merge(['recipient_phone' => NormalizeIdentity::phone($this->recipient_phone)]);
        }
    }

    public function rules(): array
    {
        return [
            'fulfillment_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'regex:/^\+84[35789][0-9]{8}$/D'],
            'address' => ['nullable', 'required_if:fulfillment_method,delivery', Rule::prohibitedIf($this->fulfillment_method === 'pickup'), 'array:address_line,locality,province'],
            'address.address_line' => ['required_with:address', 'string', 'max:255'],
            'address.locality' => ['required_with:address', 'string', 'max:120'],
            'address.province' => ['required_with:address', 'string', 'max:120'],
        ];
    }
}
