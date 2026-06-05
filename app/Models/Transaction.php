<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'subscription_plan_id', 'transaction_type',
        'vnp_txn_ref', 'amount', 'status', 'vnp_transaction_no',
        'vnp_response_code', 'vnp_bank_code', 'vnp_secure_hash',
        'order_info', 'vnp_pay_date', 'ip_address',
        'billing_cycle', 'is_auto_renewal', 'previous_subscription_plan_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_auto_renewal' => 'boolean',
            'vnp_pay_date' => 'datetime',
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