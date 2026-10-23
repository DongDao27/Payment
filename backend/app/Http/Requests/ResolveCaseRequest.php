<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ResolveCaseRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['resolution' => ['required', Rule::in(['verified_unpaid', 'refund_verified', 'duplicate_refund_verified', 'manual_review'])], 'note' => ['required', 'string', 'min:5', 'max:2000'], 'evidence_reference' => ['sometimes', 'nullable', 'string', 'max:255'], 'version' => ['required', 'integer', 'min:1']];
    }
}
