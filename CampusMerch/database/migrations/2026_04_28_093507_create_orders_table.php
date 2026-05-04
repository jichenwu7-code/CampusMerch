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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('用户ID');
            $table->foreignId('product_id')->constrained()->comment('商品ID');
            $table->integer('qty')->default(1)->comment('数量');
            $table->json('preference')->nullable()->comment('定制偏好');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('status')->default('pending')->comment('状态:pending待处理,reviewing审核中,ready待收货,completed已完成,rejected已拒绝');
            $table->string('design_url')->nullable()->comment('设计稿URL');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};