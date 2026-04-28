<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminStat extends Model
{
    use HasFactory;

    // 管理员数据看板统计
    public static function getAdminStats()
    {
        $todayOrderCount = DB::table('orders')
            ->whereDate('created_at', today())
            ->count();

        $pendingReviewCount = DB::table('orders')
            ->where('status', 'reviewing')
            ->count();

        $stockWarningProducts = Product::where('stock', '<=', 10)
            ->select('id', 'name', 'stock')
            ->get()
            ->toArray();

        $totalSalesAmount = DB::table('orders')
            ->whereIn('status', ['completed', 'shipped'])
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select(DB::raw('sum(products.price * orders.qty) as total'))
            ->first()
            ->total ?? 0;

        return [
            'today_order_count' => $todayOrderCount,
            'pending_review_count' => $pendingReviewCount,
            'stock_warning_products' => $stockWarningProducts,
            'total_sales_amount' => number_format($totalSalesAmount, 2)
        ];
    }
}