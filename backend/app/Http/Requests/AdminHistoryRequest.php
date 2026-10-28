<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AdminHistoryRequest extends StrictFormRequest
{
    public function rules(): array
    {
        $rules = [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ];
        if ($this->route('order')) {
            $rules['type'] = ['sometimes', Rule::in(['attempts', 'receipts', 'statuses'])];
        }

        return $rules;
    }
}
