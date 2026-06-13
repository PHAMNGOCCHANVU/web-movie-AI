<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    protected $table = 'subscription_history';

    protected $fillable = [
        'user_id', 'subscription_plan_id', 'previous_subscription_plan_id',
        'action', 'amount', 'billing_cycle', 'reason',
        'subscription_starts_at', 'subscription_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'subscription_starts_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function previousSubscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'previous_subscription_plan_id');
    }
}
