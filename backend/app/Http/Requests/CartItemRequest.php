<?php

namespace App\Http\Requests;

class CartItemRequest extends StrictFormRequest
{
    public function rules(): array
    {
        $rules = ['quantity' => ['required', 'integer', 'between:1,99']];
        if ($this->isMethod('POST')) {
            $rules['product_id'] = ['required', 'integer', 'exists:products,id'];
        }

        return $rules;
    }
}
