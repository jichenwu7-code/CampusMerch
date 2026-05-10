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
        Schema::table('orders', function (Blueprint $table) {
            // 检查字段不存在才添加，避免报错
            if (!Schema::hasColumn('orders', 'user_id')) {
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('orders', 'product_id')) {
                $table->unsignedBigInteger('product_id');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            }
            if (!Schema::hasColumn('orders', 'qty')) {
                $table->integer('qty');
            }
            if (!Schema::hasColumn('orders', 'preference')) {
                $table->json('preference')->nullable();
            }
            if (!Schema::hasColumn('orders', 'remark')) {
                $table->text('remark')->nullable();
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $table->tinyInteger('status')->default(0);
            }
            if (!Schema::hasColumn('orders', 'design_url')) {
                $table->string('design_url')->nullable();
            }
            if (!Schema::hasColumn('orders', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['orders_user_id_foreign']);
            $table->dropForeign(['orders_product_id_foreign']);
            $table->dropColumn([
                'user_id', 'product_id', 'qty', 'preference',
                'remark', 'status', 'design_url', 'reviewed_at'
            ]);
        });
    }
};
