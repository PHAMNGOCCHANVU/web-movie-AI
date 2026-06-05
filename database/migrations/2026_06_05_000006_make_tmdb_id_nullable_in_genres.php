<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            $table->integer('tmdb_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            // Cần set giá trị mặc định trước khi đổi về unique
            DB::table('genres')->whereNull('tmdb_id')->update(['tmdb_id' => 0]);
            $table->integer('tmdb_id')->unique()->change();
        });
    }
};