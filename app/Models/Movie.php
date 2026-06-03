<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'name', 'tmdb_id', 'description', 'release_date', 'duration', 
        'poster_url', 'trailer_url', 'is_premium', 'is_pinned'
    ];

    // Liên kết: 1 Bộ phim thuộc về Nhiều Thể loại
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'genre_movie', 'movie_id', 'genre_id');
    }

    // Liên kết: 1 Bộ phim có Nhiều Bình luận
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}