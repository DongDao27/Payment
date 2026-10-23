<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

class RevenueRequest extends StrictFormRequest
{
    public function rules(): array
    {
        return ['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after:from', 'before_or_equal:'.now('Asia/Ho_Chi_Minh')->addDay()->toDateString()], 'granularity' => ['sometimes', Rule::in(['day', 'week', 'month'])]];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (! $validator->errors()->hasAny(['from', 'to']) && CarbonImmutable::parse($this->from)->diffInDays(CarbonImmutable::parse($this->to)) > 366) {
                $validator->errors()->add('to', 'Report range must be at most 366 days.');
            }
        }];
    }
}
