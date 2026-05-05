<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class WjcController
{
    //公共接口

    //商品列表
    public function productList(Request $request)
    {
        // 调试：查看总记录数
        $totalCount = Product::count();
        logger()->info('Total products in DB: ' . $totalCount);
        logger()->info('Request params: ', $request->all());
        
        // 暂时不使用任何过滤，直接查询所有
        $query = Product::query();
        
        // 只有当参数有实际值时才过滤
        if($request->filled('category')){
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->filled('keyword')) {
            $query->where('name', 'like', "%{$request->keyword}%");
        }

        $list = $query->paginate($request->per_page ?? 20);
        
        logger()->info('Query SQL: ' . $query->toSql());
        logger()->info('Query result count: ' . $list->total());

        return response()->json([
            'code'=>200,
            'message'=>'查询成功',
            'data' => [
                'list' => $list->items(),
                'total' => $list->total(),
                'page' => $list->currentPage(),
                'per_page' => $list->perPage(),
                'debug_total_in_db' => $totalCount
            ]
        ]);
    }

    // 商品详情查询
    public function productDetail($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'code' => 422,
                    'message' => '商品ID格式错误',
                    'data' => null,
                    'errors' => ['ID必须是正整数']
                ], 422);
            }

            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'code' => 404,
                    'message' => '商品不存在',
                    'data' => null,
                    'errors' => ['商品未找到']
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'message' => '查询成功',
                'data' => $product,
                'errors' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '服务器错误：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    //首页统计
    public function homeStats()
    {
        return response()->json([
            'code' => 200,
            'message' => '查询成功',
            'data' => [
                'total_products' => Product::where('status', 1)->count(),
                'today_orders' => Order::whereDate('created_at', today())->count(),
                'hot_categories' => Product::select('category')
                    ->groupBy('category')
                    ->orderByRaw('count(*) desc')
                    ->limit(3)
                    ->pluck('category')
            ]
        ]);
    }

    // 定制规则
    public function customRules()
    {
        return response()->json([
            'code' => 200,
            'message' => '查询成功',
            'data' => [
                'common_rule' => '所有定制文件分辨率≥300DPI，单文件≤20MB，内容合规',
                'category_rules' => [
                    '文创' => '图案尺寸不超过商品表面80%',
                    '物料' => '文字内容需符合审核要求'
                ]
            ]
        ]);
    }


    // 管理员接口

    // 修改商品信息
    public function updateProduct(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'code' => 422,
                    'message' => '商品ID格式错误',
                    'data' => null,
                    'errors' => ['ID必须是正整数']
                ], 422);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:100',
                'price' => 'nullable|numeric|min:0',
                'stock' => 'nullable|integer|min:0',
                'status' => 'nullable|integer|in:0,1',
                'custom_rule' => 'nullable|string|max:1000',
            ]);

            $product = Product::find($id);
            if (!$product) {
                return response()->json([
                    'code' => 404,
                    'message' => '商品不存在',
                    'data' => null,
                    'errors' => ['商品未找到']
                ], 404);
            }

            // 只更新有值的字段
            $updateData = array_filter($request->only([
                'name', 'category', 'price', 'stock', 'status', 'custom_rule'
            ]), function($value) {
                return $value !== null;
            });
            
            $product->fill($updateData);
            $product->version = ($product->version ?? 0) + 1;
            $product->save();

            return response()->json([
                'code' => 200,
                'message' => '商品修改成功',
                'data' => ['id' => $id, 'version' => $product->version],
                'errors' => []
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => null,
                'errors' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '修改失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    // 订单审核
    public function reviewOrder(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'code' => 422,
                    'message' => '订单ID格式错误',
                    'data' => null,
                    'errors' => ['ID必须是正整数']
                ], 422);
            }

            $validated = $request->validate([
                'result' => 'required|in:pass,reject',
                'remark' => 'nullable|string|max:500',
            ]);

            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'code' => 404,
                    'message' => '订单不存在',
                    'data' => null,
                    'errors' => ['订单未找到']
                ], 404);
            }

            DB::transaction(function () use ($order, $request, $id) {
                $order->status = $request->result === 'pass' ? 'ready' : 'rejected';
                $order->reviewed_at = now();
                $order->save();

                // 记录日志
                AuditLog::create([
                    'operator_id' => 1,
                    'operator_type' => 'admin',
                    'target_type' => 'order',
                    'target_id' => $id,
                    'action' => 'review',
                    'content' => json_encode(['result' => $request->result, 'remark' => $request->remark])
                ]);
            });

            return response()->json([
                'code' => 200,
                'message' => '订单审核成功',
                'data' => null,
                'errors' => []
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => null,
                'errors' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '审核失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    // 管理员看板数据
    public function adminStats()
    {
        try {
            $data = [
                'today_order_count' => Order::whereDate('created_at', today())->count(),
                'pending_review_count' => Order::where('status', 'reviewing')->count(),
                'stock_warning_products' => Product::where('stock', '<=', 10)->get(['id', 'name', 'stock']),
                'total_sales_amount' => Order::whereIn('orders.status', ['completed', 'ready'])
                    ->join('products', 'orders.product_id', '=', 'products.id')
                    ->sum(DB::raw('products.price * orders.qty'))
            ];

            return response()->json([
                'code' => 200,
                'message' => '查询成功',
                'data' => $data,
                'errors' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '查询失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    // 操作日志列表
    public function operationLogs(Request $request)
    {
        try {
            $validated = $request->validate([
                'operation_type' => 'nullable|string|max:50',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $logs = AuditLog::when($request->has('operation_type'), function ($q) use ($request) {
                $q->where('action', $request->operation_type);
            })->latest()->paginate($request->per_page ?? 20);

            return response()->json([
                'code' => 200,
                'message' => '查询成功',
                'data' => [
                    'list' => $logs->items(),
                    'total' => $logs->total(),
                    'page' => $logs->currentPage(),
                    'per_page' => $logs->perPage()
                ],
                'errors' => []
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => null,
                'errors' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '查询失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    // 订单状态强制修改
    public function updateOrderStatus(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'code' => 422,
                    'message' => '订单ID格式错误',
                    'data' => null,
                    'errors' => ['ID必须是正整数']
                ], 422);
            }

            $validated = $request->validate([
                'status' => 'required|string|in:pending,reviewing,ready,rejected,completed',
                'reason' => 'required|string|max:500',
            ]);

            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'code' => 404,
                    'message' => '订单不存在',
                    'data' => null,
                    'errors' => ['订单未找到']
                ], 404);
            }

            DB::transaction(function () use ($order, $request, $id) {
                $order->status = $request->status;
                $order->save();

                AuditLog::create([
                    'operator_id' => 1,
                    'operator_type' => 'admin',
                    'target_type' => 'order',
                    'target_id' => $id,
                    'action' => 'status_change',
                    'content' => json_encode(['new_status' => $request->status, 'reason' => $request->reason])
                ]);
            });

            return response()->json([
                'code' => 200,
                'message' => '订单状态修改成功',
                'data' => null,
                'errors' => []
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => null,
                'errors' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '修改失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    // 商品库存批量调整
    public function batchUpdateStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.stock' => 'required|integer|min:0',
            ]);

            $updatedCount = 0;
            $notFound = [];

            DB::transaction(function () use ($request, &$updatedCount, &$notFound) {
                foreach ($request->items as $item) {
                    $p = Product::find($item['product_id']);
                    if ($p) {
                        $p->stock = $item['stock'];
                        $p->save();
                        $updatedCount++;
                    } else {
                        $notFound[] = $item['product_id'];
                    }
                }
            });

            return response()->json([
                'code' => 200,
                'message' => '批量调整库存成功',
                'data' => [
                    'updated_count' => $updatedCount,
                    'not_found' => $notFound
                ],
                'errors' => []
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => null,
                'errors' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '调整失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    // 商品批量导入
    public function importProducts(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            ]);

            $import = new ProductsImport();
            Excel::import($import, $request->file('file'));

            return response()->json([
                'code' => 200,
                'message' => '导入成功',
                'data' => ['imported_count' => $import->getRowCount()],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => null,
                'errors' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '导入失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }   
    }

    // 管理员手动核销订单
    public function verifyOrder($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'code' => 422,
                    'message' => '订单ID格式错误',
                    'data' => null,
                    'errors' => ['ID必须是正整数']
                ], 422);
            }

            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'code' => 404,
                    'message' => '订单不存在',
                    'data' => null,
                    'errors' => ['订单未找到']
                ], 404);
            }

            // 检查订单状态是否允许核销
            if (!in_array($order->status, ['ready', 'completed'])) {
                return response()->json([
                    'code' => 400,
                    'message' => '订单状态不允许核销',
                    'data' => null,
                    'errors' => ['当前订单状态为: ' . $order->status . ', 无法核销']
                ], 400);
            }

            DB::transaction(function () use ($order, $id) {
                $order->status = 'completed';
                $order->verified_at = now();
                $order->save();

                AuditLog::create([
                    'operator_id' => 1,
                    'operator_type' => 'admin',
                    'target_type' => 'order',
                    'target_id' => $id,
                    'action' => 'verify',
                    'content' => json_encode(['status' => 'completed', 'verified_at' => now()])
                ]);
            });

            return response()->json([
                'code' => 200,
                'message' => '订单核销成功',
                'data' => null,
                'errors' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '核销失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }


    // 用户管理列表
    public function userList(Request $request)
    {
        try {
            $validated = $request->validate([
                'keyword' => 'nullable|string|max:100',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = \App\Models\User::query();

            if ($request->has('keyword')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->keyword . '%')
                      ->orWhere('email', 'like', '%' . $request->keyword . '%');
                });
            }

            $list = $query->paginate($request->per_page ?? 20);

            return response()->json([
                'code' => 200,
                'message' => '查询成功',
                'data' => [
                    'list' => $list->items(),
                    'total' => $list->total(),
                    'page' => $list->currentPage(),
                    'per_page' => $list->perPage()
                ],
                'errors' => []
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => null,
                'errors' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '查询失败：' . $e->getMessage(),
                'data' => null,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }
}