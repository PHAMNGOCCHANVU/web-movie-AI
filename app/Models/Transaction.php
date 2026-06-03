<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vnp_txn_ref',
        'amount',
        'status',
        'vnp_transaction_no'
    ];

    // Một giao dịch do Một người dùng thực hiện
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}