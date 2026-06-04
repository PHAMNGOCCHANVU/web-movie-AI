<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    protected $fillable = [
        'movie_id',
        'name',
        'stream_url'
    ];

    // Liên kết: 1 Tập phim thuộc về 1 Bộ phim
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }
}