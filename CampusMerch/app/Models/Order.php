<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'qty',
        'preference',   // JSON 字符串
        'remark',
        'status',
        'design_url',
        'reviewed_at',
    ];

    protected $casts = [
        'preference' => 'array', // 自动把 JSON 转为数组
        'reviewed_at' => 'datetime',
    ];

    public function product()
    {
        // 订单 belongsTo 商品，外键是 product_id
        return $this->belongsTo(Product::class);
    }
}
