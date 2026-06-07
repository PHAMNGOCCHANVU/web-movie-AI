<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration_in_days',
        'description'
    ];

    // Mối quan hệ: 1 Gói VIP có thể được mua bởi nhiều Người dùng
    public function users()
    {
        return $this->hasMany(User::class, 'vip_package_id');
    }
}