<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Order;
use App\Models\Collection;
use App\Models\Product;

class GyzController extends Controller
{
    // 1. 提交预订单
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'preference' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // 库存判断
        if ($product->stock < $validated['qty']) {
            return response()->json([
                'code' => 400,
                'message' => '库存不足',
                'data' => null
            ], 400);
        }

        $order = Order::create([
            'user_id' => auth()->id(),
            'product_id' => $validated['product_id'],
            'qty' => $validated['qty'],
            'preference' => $validated['preference'] ?? '',
            'status' => 'booked',
        ]);

        // 增加预留库存
        $product->increment('reserved_qty', $validated['qty']);

        return response()->json([
            'code' => 200,
            'message' => '预订单创建成功',
            'data' => $order
        ]);
    }

    // 2. 上传定制稿
    public function uploadDesign(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'design_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
        ]);

        $path = $request->file('design_file')->store('designs', 'public');
        $designUrl = Storage::url($path);

        $order->update([
            'design_url' => $designUrl,
            'status' => 'design_pending',
        ]);

        return response()->json([
            'code' => 200,
            'message' => '上传成功',
            'data' => ['design_url' => $designUrl]
        ]);
    }

    // 3. 确认收货
    public function completeOrder($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'shipped')
            ->firstOrFail();

        $order->update(['status' => 'completed']);

        $product = $order->product;
        // 扣减真实库存 + 释放预留库存
        $product->decrement('stock', $order->qty);
        $product->decrement('reserved_qty', $order->qty);

        return response()->json([
            'code' => 200,
            'message' => '确认收货成功'
        ]);
    }

    // 4. 我的订单
    public function myOrders(Request $request)
    {
        $query = Order::where('user_id', auth()->id())
            ->with('product:id,name,price,cover_url');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $data = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $data
        ]);
    }

    // 5. 我的收藏
    public function myCollections()
    {
        // 用 Collection 模型的 user_id、product_id 字段
        $data = Collection::where('user_id', auth()->id())
            ->with('product:id,name,price,cover_url')
            ->paginate(20);

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    // 6. 收藏商品
    public function storeCollection(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        // 避免重复收藏
        Collection::firstOrCreate([
            'user_id' => auth()->id(),
            'product_id' => $validated['product_id']
        ]);

        return response()->json([
            'code' => 200,
            'message' => '收藏成功'
        ]);
    }

    // 7. 取消收藏
    public function destroyCollection($product_id)
    {
        // 按 user_id + product_id 删除，完全对应 Collection 模型字段
        Collection::where('user_id', auth()->id())
            ->where('product_id', $product_id)
            ->delete();

        return response()->json([
            'code' => 200,
            'message' => '取消成功'
        ]);
    }
}
