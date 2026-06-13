<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageBlock extends Model
{
    protected $fillable = [
        'title', 'source_type', 'source_ref', 'is_visible', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}