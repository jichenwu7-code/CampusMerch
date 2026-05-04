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
            $table->string('name')->comment('商品名称');
            $table->string('category')->nullable()->comment('分类');
            $table->string('type')->nullable()->comment('类型');
            $table->json('spec')->nullable()->comment('规格');
            $table->decimal('price', 10, 2)->default(0)->comment('价格');
            $table->integer('stock')->default(0)->comment('库存');
            $table->integer('reserved_qty')->default(0)->comment('预留库存');
            $table->string('cover_url')->nullable()->comment('封面图');
            $table->text('custom_rule')->nullable()->comment('定制规则');
            $table->integer('sold_count')->default(0)->comment('销量');
            $table->tinyInteger('status')->default(1)->comment('状态:0下架1上架');
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
