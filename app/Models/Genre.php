<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $fillable = ['tmdb_id', 'name'];

    // Bổ sung hàm này để nhận diện mối quan hệ ngược lại với Phim
    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'genre_movie', 'genre_id', 'movie_id');
    }
}