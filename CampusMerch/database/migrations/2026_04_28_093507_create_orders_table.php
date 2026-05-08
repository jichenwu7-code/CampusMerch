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
            $table->unsignedBigInteger('user_id'); // 用户ID
            $table->unsignedBigInteger('product_id'); // 商品ID
            $table->integer('qty'); // 购买数量
            $table->json('preference')->nullable(); // 定制偏好（JSON格式）
            $table->text('remark')->nullable(); // 用户备注
            $table->tinyInteger('status')->default(0); // 订单状态（0待处理/1已确认/2已发货等）
            $table->string('design_url')->nullable(); // 设计文件地址
            $table->timestamp('reviewed_at')->nullable(); // 审核时间
            $table->timestamps();

            // 外键关联
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
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
