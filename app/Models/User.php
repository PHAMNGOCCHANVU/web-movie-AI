<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 1. Cấp quyền cho Admin được phép sửa các cột này
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',          
        'is_locked',        
        'vip_package_id',
        'vip_expires_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 2. Ép kiểu dữ liệu chuẩn xác
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_locked' => 'boolean', 
            'vip_expires_at' => 'datetime'
        ];
    }

    // 3. Khai báo quan hệ OOP để tự động nối bảng
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function vipPackage()
    {
        return $this->belongsTo(VipPackage::class, 'vip_package_id');
    }
}