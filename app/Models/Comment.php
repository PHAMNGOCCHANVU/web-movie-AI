<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'movie_id',
        'content',
        'toxic_score',
        'is_hidden'
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'toxic_score' => 'float', // Ép kiểu điểm số toxic
        ];
    }

    // 1 Bình luận thuộc về 1 Người dùng
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 1 Bình luận thuộc về 1 Bộ phim
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }
}