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
            // 检查字段不存在才添加，避免报错
            if (!Schema::hasColumn('products', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable();
            }
            if (!Schema::hasColumn('products', 'type')) {
                $table->string('type')->nullable();
            }
            if (!Schema::hasColumn('products', 'spec')) {
                $table->json('spec')->nullable();
            }
            if (!Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2);
            }
            if (!Schema::hasColumn('products', 'stock')) {
                $table->integer('stock')->default(0);
            }
            if (!Schema::hasColumn('products', 'reserved_qty')) {
                $table->integer('reserved_qty')->default(0);
            }
            if (!Schema::hasColumn('products', 'cover_url')) {
                $table->string('cover_url')->nullable();
            }
            if (!Schema::hasColumn('products', 'custom_rule')) {
                $table->text('custom_rule')->nullable();
            }
            if (!Schema::hasColumn('products', 'sold_count')) {
                $table->integer('sold_count')->default(0);
            }
            if (!Schema::hasColumn('products', 'status')) {
                $table->tinyInteger('status')->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'category', 'type', 'spec', 'price', 'stock',
                'reserved_qty', 'cover_url', 'custom_rule', 'sold_count', 'status'
            ]);
        });
    }
};
