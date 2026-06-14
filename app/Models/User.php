<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'phone',
        'password',
        'role_id',
        'subscription_plan_id',
        'subscription_starts_at',
        'subscription_expires_at',
        'subscription_status',
        'auto_renew',
        'cancelled_at',
        'cancellation_reason',
        'is_locked',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_starts_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_locked' => 'boolean',
            'auto_renew' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscriptionHistories(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function aiChatConversations(): HasMany
    {
        return $this->hasMany(AiChatConversation::class);
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_user')
            ->withPivot(['is_favorite', 'watch_progress_seconds', 'duration_seconds', 'episode_id', 'season_id'])
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    public function isVip(): bool
    {
        return $this->hasActiveSubscription()
            && $this->subscriptionPlan
            && str_starts_with($this->subscriptionPlan->plan_code, 'vip');
    }
}
