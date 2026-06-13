<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'movie_id',
        'content',
        'toxic_score',
        'moderation_status',
        'moderation_model',
        'moderation_score',
        'moderation_categories',
        'moderation_reason',
        'moderated_at',
        'reviewed_at',
        'reviewed_by',
        'is_hidden',
        'hidden_at',
        'hidden_by',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'hidden_at' => 'datetime',
            'toxic_score' => 'float',
            'moderation_score' => 'float',
            'moderation_categories' => 'array',
            'moderated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
