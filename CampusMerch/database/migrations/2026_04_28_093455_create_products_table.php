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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 商品名称
            $table->string('category')->nullable(); // 分类
            $table->string('type')->nullable(); // 类型
            $table->json('spec')->nullable(); // 规格（对应模型里的array）
            $table->decimal('price', 10, 2); // 价格，保留两位小数
            $table->integer('stock')->default(0); // 库存
            $table->integer('reserved_qty')->default(0); // 预扣库存
            $table->string('cover_url')->nullable(); // 封面图
            $table->text('custom_rule')->nullable(); // 定制规则
            $table->integer('sold_count')->default(0); // 销量
            $table->tinyInteger('status')->default(1); // 状态：1上架/0下架
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
