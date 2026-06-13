<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Điểm số toxic chạy từ 0.0 đến 1.0 nên dùng kiểu float hoặc double
            // nullable() cho phép các bình luận cũ chưa quét AI không bị lỗi dữ liệu
            $table->float('toxic_score')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('toxic_score');
        });
    }
};