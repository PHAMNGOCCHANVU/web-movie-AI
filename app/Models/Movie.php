<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    protected $fillable = [
        'ophim_id', 'slug', 'name', 'origin_name', 'content', 'type',
        'thumb_url', 'poster_url', 'trailer_url', 'quality', 'lang',
        'year', 'episode_current', 'episode_total', 'time',
        'actor', 'director', 'country', 'view_count', 'status', 'last_synced_at',
        'is_premium', 'is_pinned', 'stream_url', 'stream_source', 'tmdb_id',
    ];

    protected function casts(): array
    {
        return [
            'actor' => 'array',
            'director' => 'array',
            'country' => 'array',
            'is_premium' => 'boolean',
            'is_pinned' => 'boolean',
            'year' => 'integer',
            'view_count' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_movie');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'movie_user')
            ->withPivot(['is_favorite', 'watch_progress_seconds', 'episode_id', 'season_id'])
            ->withTimestamps();
    }
}