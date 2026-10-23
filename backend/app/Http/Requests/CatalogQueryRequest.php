<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CatalogQueryRequest extends StrictFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user()?->isAdmin() && $this->is('api/v1/admin/*')
            && in_array($this->input('is_active'), ['true', 'false'], true)) {
            $this->merge(['is_active' => $this->input('is_active') === 'true']);
        }
    }

    public function rules(): array
    {
        $rules = [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'integer', 'min:1'],
            'grade' => ['sometimes', 'integer', 'between:1,12'],
            'section' => ['sometimes', Rule::in(['hot', 'bestseller', 'new'])],
            'sort' => ['sometimes', Rule::in(['price_asc', 'price_desc', 'newest'])],
        ];
        if ($this->user()?->isAdmin() && $this->is('api/v1/admin/*')) {
            $rules['is_active'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }
}
