<?php

use App\Http\Controllers\ZhyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WjcController;
use App\Http\Controllers\ZhyController;

// ========== 认证接口（zhy）- 公共 ==========
Route::post('/send-verify-code', [ZhyController::class, 'sendVerifyCode']);
Route::post('/register', [ZhyController::class, 'register']);
Route::post('/login', [ZhyController::class, 'login']);
Route::post('/verify-code-check', [ZhyController::class, 'verifyCodeCheck']);
Route::post('/password/reset', [ZhyController::class, 'resetPassword']);

// ========== 认证接口（zhy）- 需登录 ==========
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ZhyController::class, 'logout']);
    Route::put('/my/profile', [ZhyController::class, 'updateProfile']);
});
// 公共接口
Route::get('/products', [WjcController::class, 'productList']);
Route::get('/products/{id}', [WjcController::class, 'productDetail']);
Route::get('/home/stats', [WjcController::class, 'homeStats']);
Route::get('/custom-rules', [WjcController::class, 'customRules']);

// 管理员接口（需要登录）
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
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
