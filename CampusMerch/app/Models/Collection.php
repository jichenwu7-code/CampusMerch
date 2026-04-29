<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    // 关联商品
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
