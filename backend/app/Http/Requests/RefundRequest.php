<?php

namespace App\Http\Requests;

class RefundRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'receipt_id' => ['required', 'integer', 'min:1'], 'external_reference' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'between:1,1000000000'],
            'merchandise_amount' => ['required', 'integer', 'between:0,1000000000'], 'shipping_amount' => ['required', 'integer', 'between:0,1000000000'],
            'refunded_at' => ['required', 'date', 'before_or_equal:now', 'regex:/T.*(?:Z|[+-][0-9]{2}:[0-9]{2})$/D'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'evidence_reference' => ['required', 'string', 'max:150', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/D', 'not_regex:/\.\./'],
        ];
    }
}
