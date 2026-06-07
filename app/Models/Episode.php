<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    protected $fillable = [
        'movie_id',
        'server_name',
        'name',
        'slug',
        'filename',
        'link_embed',
        'link_m3u8',
        'sort_order',
        'stream_url',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }
}