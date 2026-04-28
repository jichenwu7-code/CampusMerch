<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'category', 'type', 'spec', 'price', 'stock', 
        'reserved_qty', 'cover_url', 'custom_rule', 'sold_count', 'status'
    ];

    protected $casts = [
        'spec' => 'array', // 规格字段转为数组
        'price' => 'decimal:2', // 价格保留两位小数
        'status' => 'integer',
        'stock' => 'integer',
        'reserved_qty' => 'integer'
    ];

    // 库存预警判断
    public function getStockWarningAttribute()
    {
        return $this->stock <= 10; // 库存≤10触发预警
    }
}