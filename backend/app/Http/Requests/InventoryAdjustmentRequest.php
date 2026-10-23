<?php

namespace App\Http\Requests;

class InventoryAdjustmentRequest extends StrictFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['delta' => ['required', 'integer', 'between:-2147483647,2147483647', 'not_in:0'], 'reason' => ['required', 'string', 'max:1000'], 'request_key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/D']];
    }
}
