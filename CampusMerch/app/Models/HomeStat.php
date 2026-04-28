<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HomeStat extends Model
{
    use HasFactory;

    // 首页公开统计数据查询
    public static function getPublicStats()
    {
        $totalProducts = Product::where('status', 1)->count();
        
        $hotCategories = DB::table('products')
            ->select('category', DB::raw('count(*) as count'))
            ->where('status', 1)
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->pluck('category')
            ->toArray();

        $todayNewOrders = DB::table('orders')
            ->whereDate('created_at', today())
            ->count();

        return [
            'total_products' => $totalProducts,
            'hot_categories' => $hotCategories,
            'today_new_orders' => $todayNewOrders
        ];
    }

    // 通用定制规则查询
    public static function getCustomRules()
    {
        return [
            'common_rule' => '所有定制文件分辨率≥300DPI，单文件≤20MB',
            'category_rules' => [
                '文创' => '图案尺寸不超过商品表面80%',
                '物料' => '文字内容需符合合规审核要求'
            ]
        ];
    }
}