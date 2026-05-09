<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->string('status', 50)->change();
        });
        \DB::table('products')
            ->where('status', 1)
            ->update(['status' => 'booked']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->tinyInteger('status')->change();
        });

        // 回滚时把字符串改回数字（可选）
        \DB::table('products')
            ->where('status', 'booked')
            ->update(['status' => 1]);

    }
};
