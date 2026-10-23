<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class FulfillmentRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['target_status' => ['required', Rule::in(['preparing', 'ready_for_pickup', 'shipping', 'fulfilled'])], 'version' => ['required', 'integer', 'min:1']];
    }
}
