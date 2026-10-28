<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductRequest extends StrictFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('POST') ? 'required' : 'sometimes';
        $id = $this->route('product')?->id;
        $rules = [
            'category_id' => [$presence, 'integer', 'exists:categories,id'],
            'sku' => [$presence, 'string', 'max:100', Rule::unique('products')->ignore($id)],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('products')->ignore($id)],
            'name' => [$presence, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'grade' => ['sometimes', 'nullable', 'integer', 'between:1,12'],
            'list_price' => [$presence, 'integer', 'between:0,1000000000'],
            'sale_price' => ['sometimes', 'nullable', 'integer', 'between:0,1000000000', 'prohibits:discount_percent'],
            'discount_percent' => ['sometimes', 'integer', 'between:0,100', 'prohibits:sale_price'],
            'is_active' => ['sometimes', 'boolean'],
            'is_hot' => ['sometimes', 'boolean'],
            'is_bestseller' => ['sometimes', 'boolean'],
        ];
        if ($this->isMethod('POST')) {
            $rules['opening_stock'] = ['required', 'integer', 'between:0,2147483647'];
        } else {
            $rules['version'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Mã SKU này đã được sử dụng. Vui lòng nhập mã khác.',
            'category_id.exists' => 'Danh mục không còn tồn tại. Vui lòng tải lại trang.',
        ];
    }
}
