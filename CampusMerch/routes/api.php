<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WjcController;
use App\Http\Controllers\GyzController;

// 公共接口
Route::get('/products', [WjcController::class, 'productList']);
Route::get('/products/{id}', [WjcController::class, 'productDetail']);
Route::get('/home/stats', [WjcController::class, 'homeStats']);
Route::get('/custom-rules', [WjcController::class, 'customRules']);

// 管理员接口
Route::prefix('admin')->group(function () {
    // 商品相关
    Route::post('/products/import', [WjcController::class, 'importProducts']);
    Route::put('/products/{id}', [WjcController::class, 'updateProduct']);
    Route::post('/products/stock/batch', [WjcController::class, 'batchUpdateStock']);

    // 订单相关
    Route::put('/orders/{id}/review', [WjcController::class, 'reviewOrder']);
    Route::post('/orders/{id}/verify', [WjcController::class, 'verifyOrder']);
    Route::put('/orders/{id}/status', [WjcController::class, 'updateOrderStatus']);

    // 用户管理
    Route::get('/users', [WjcController::class, 'userList']);

    // 统计和日志
    Route::get('/stats', [WjcController::class, 'adminStats']);
    Route::get('/logs', [WjcController::class, 'operationLogs']);
});
Route::middleware('auth:sanctum')->group(function () {
    // 订单
    Route::post('/orders', [GyzController::class, 'storeOrder']);
    Route::post('/orders/{id}/design', [GyzController::class, 'uploadDesign']); // 上传定制稿// 提交预订单
    Route::post('/orders/{id}/complete', [GyzController::class, 'completeOrder']); // 确认收货
    Route::get('/my/orders', [GyzController::class, 'myOrders']); // 我的订单

    // 收藏
    Route::get('/my/collections', [GyzController::class, 'myCollections']); // 我的收藏
    Route::post('/my/collections', [GyzController::class, 'storeCollection']); // 收藏商品
    Route::delete('/my/collections/{product_id}', [GyzController::class, 'destroyCollection']); // 取消收藏
});


