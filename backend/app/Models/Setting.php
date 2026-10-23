<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'key',
        'value',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'version' => 'integer',
        ];
    }
}
