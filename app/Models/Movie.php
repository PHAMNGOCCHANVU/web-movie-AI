<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    // Thêm 'status' vào fillable
    protected $fillable = [
        'name', 'tmdb_id', 'description', 'release_date', 'duration', 
        'poster_url', 'trailer_url', 'is_premium', 'is_pinned', 'status'
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'genre_movie', 'movie_id', 'genre_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Liên kết: 1 Bộ phim có Nhiều Tập phim
    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }
}