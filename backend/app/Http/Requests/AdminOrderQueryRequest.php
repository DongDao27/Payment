<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AdminOrderQueryRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'fulfillment_method' => ['sometimes', Rule::in(['delivery', 'pickup'])],
            'payment_status' => ['sometimes', Rule::in(['unpaid', 'pending', 'paid', 'reconciliation_required'])],
            'fulfillment_status' => ['sometimes', Rule::in(['unprocessed', 'preparing', 'ready_for_pickup', 'shipping', 'fulfilled'])],
            'from' => ['sometimes', 'date_format:Y-m-d'], 'to' => ['sometimes', 'date_format:Y-m-d', Rule::when($this->has('from'), ['after:from'])],
        ];
    }
}
