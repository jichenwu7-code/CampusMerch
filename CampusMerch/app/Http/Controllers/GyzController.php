<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GyzController extends Controller
{
    //1.提交预订单
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|integer|min:1',
            'preference' => 'nullable|array',
            'remark'     => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['qty']) {
            return response()->json([
                'code'    => 400,
                'message' => '库存不足',
                'data'    => null,
                'errors'  => [],
            ], 400);
        }

        $order = DB::transaction(function () use ($validated, $product) {
            $product->decrement('stock', $validated['qty']);
            $product->increment('reserved_qty', $validated['qty']);

            return Order::create([
                'user_id'    => Auth::id(),
                'product_id' => $validated['product_id'],
                'qty'        => $validated['qty'],
                'preference' => json_encode($validated['preference'] ?? []),
                'remark'     => $validated['remark'] ?? '',
                'status'     => 'booked',
                'design_url' => null,
            ]);
        });

        return response()->json([
            'code'    => 200,
            'message' => '预订提交成功',
            'data'    => [
                'order_id'     => $order->id,
                'status'       => $order->status,
                'reserved_qty' => $validated['qty'],
            ],
            'errors'  => [],
        ]);
    }

     //2.上传定制稿

    public function uploadDesign(Request $request, $id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);

        if (!in_array($order->status, ['booked', 'design_pending'])) {
            return response()->json([
                'code'    => 400,
                'message' => '当前订单状态不允许上传设计稿',
                'data'    => null,
                'errors'  => [],
            ], 400);
        }

        $request->validate([
            'file' => 'required|file|mimes:jpg,png,pdf|max:20480',
        ]);

        $file = $request->file('file');
        $path = sprintf(
            'merch-designs/%s/%s_%s.%s',
            $order->id,
            time(),
            uniqid(),
            $file->getClientOriginalExtension()
        );
        $designUrl = 'https://oss.example.com/' . $path;

        $order->update([
            'design_url' => $designUrl,
            'status'     => 'design_pending',
        ]);

        return response()->json([
            'code'    => 200,
            'message' => '上传成功',
            'data'    => [
                'design_url'   => $designUrl,
                'order_status' => 'design_pending',
            ],
            'errors'  => [],
        ]);
    }


     //3.用户确认收货
    public function confirmComplete($id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);

        if ($order->status !== 'shipped') {
            return response()->json([
                'code'    => 400,
                'message' => '只有已发货的订单才能确认收货',
                'data'    => null,
                'errors'  => [],
            ], 400);
        }

        $product = Product::findOrFail($order->product_id);

        DB::transaction(function () use ($order, $product) {
            $order->update(['status' => 'completed']);
            $product->decrement('reserved_qty', $order->qty);
        });

        $finalStock = $product->fresh()->stock;

        return response()->json([
            'code'    => 200,
            'message' => '确认收货成功',
            'data'    => [
                'order_id'    => $order->id,
                'status'      => 'completed',
                'final_stock' => $finalStock,
            ],
            'errors'  => [],
        ]);
    }

    //4.我的订单列表
    public function myOrders(Request $request)
    {
        $validated = $request->validate([
            'status'   => 'nullable|in:booked,design_pending,reviewing,ready,shipped,completed,cancelled',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $status  = $validated['status'] ?? null;

        $query = Order::where('user_id', Auth::id())
            ->with('product:id,name,price,cover_url')
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage);

        $list = $paginator->map(function ($order) {
            return [
                'id'           => $order->id,
                'product_name' => $order->product->name ?? '',
                'qty'          => $order->qty,
                'price'        => $order->product->price ?? 0,
                'status'       => $order->status,
                'design_url'   => $order->design_url,
                'created_at'   => $order->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'code'    => 200,
            'message' => '查询成功',
            'data'    => [
                'list'     => $list,
                'total'    => $paginator->total(),
                'page'     => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
            'errors'  => [],
        ]);
    }

    //5.我的收藏列表
    public function myCollections(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $collections = Collection::where('user_id', Auth::id())
            ->with('product:id,name,cover_url,price')
            ->latest()
            ->paginate($perPage);

        $list = $collections->map(function ($item) {
            return [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product->name ?? '',
                'cover_url'    => $item->product->cover_url ?? '',
                'collect_time' => $item->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'code'    => 200,
            'message' => '查询成功',
            'data'    => [
                'list'     => $list,
                'total'    => $collections->total(),
                'page'     => $collections->currentPage(),
                'per_page' => $collections->perPage(),
            ],
            'errors'  => [],
        ]);
    }

    //6.收藏商品
    public function addCollection(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = Auth::id();
        $exists = Collection::where('user_id', $userId)
            ->where('product_id', $validated['product_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'code'    => 400,
                'message' => '已收藏该商品',
                'data'    => null,
                'errors'  => [],
            ], 400);
        }

        $collection = Collection::create([
            'user_id'    => $userId,
            'product_id' => $validated['product_id'],
        ]);

        return response()->json([
            'code'    => 200,
            'message' => '收藏成功',
            'data'    => ['collection_id' => $collection->id],
            'errors'  => [],
        ]);
    }

   //7.取消收藏
    public function removeCollection($productId)
    {
        $affected = Collection::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->delete();

        if ($affected === 0) {
            return response()->json([
                'code'    => 400,
                'message' => '未收藏该商品或商品不存在',
                'data'    => null,
                'errors'  => [],
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '取消收藏成功',
            'data' => [
                'product_id' => $productId,
                'is_collected' => false
            ],
            'errors' => [],
        ]);
    }
    public function uploadAvatar(Request $request)
    {
        // 1. 校验文件
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,png|max:5120', // 限制 5MB
        ]);

        // 2. 获取当前用户
        $user = auth()->user();

        // 3. 保存头像文件到 public 盘
        $path = $request->file('avatar')->store('avatars', 'public');

        // 4. 更新用户头像URL
        $user->update([
            'avatar_url' => Storage::url($path),
        ]);

        // 5. 返回成功响应
        return response()->json([
            'code' => 200,
            'message' => '头像上传成功',
            'data' => [
                'avatar_url' => $user->avatar_url,
                'update_time' => now()->format('Y-m-d H:i:s'),
            ],
            'errors' => [],
        ]);
    }

}
