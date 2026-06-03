<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // Mối quan hệ: 1 Quyền có thể được gán cho nhiều Người dùng
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}