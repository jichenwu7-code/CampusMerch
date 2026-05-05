<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WjcController;
use App\Http\Controllers\ZhyController;
use App\Http\Controllers\GyzController;

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

// 用户接口（需要登录）
Route::middleware('auth:sanctum')->group(function () {
    // 1. 创建预订单
    Route::post('/orders', [GyzController::class, 'storeOrder']);
    // 2. 订单上传设计稿
    Route::post('/orders/{id}/design', [GyzController::class, 'uploadDesign']);
    // 3. 用户确认收货
    Route::post('/orders/{id}/complete', [GyzController::class, 'confirmComplete']);
    // 4. 获取我的订单列表
    Route::get('/my/orders', [GyzController::class, 'myOrders']);
    // 5. 获取我的收藏列表
    Route::get('/my/collections', [GyzController::class, 'myCollections']);
    // 6. 添加商品收藏
    Route::post('/my/collections', [GyzController::class, 'addCollection']);
    // 7. 取消商品收藏
    Route::delete('/my/collections/{productId}', [GyzController::class, 'removeCollection']);
    });
