<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CategoryRequest extends StrictFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$presence, 'string', 'max:120'],
            'slug' => [$presence, 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('categories')->ignore($this->route('category')?->id)],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'between:-2147483648,2147483647'],
        ];
    }
}
