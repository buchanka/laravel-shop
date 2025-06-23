<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\FavoriteController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\OrderSuccessController;
use App\Http\Controllers\CategoryController;

// Публичные маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::get('/collections', [CollectionController::class, 'index']);
Route::get('/products/{product}/collections', [CollectionController::class, 'getCollectionsByProduct']);
Route::get('/order-success/{order}', [OrderSuccessController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
// Защищенные маршруты (JWT)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); 
    
    // Маршруты покупателя
    Route::prefix('customer')->middleware(['auth:api', 'role:customer'])->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('avatar', [AuthController::class, 'uploadAvatar']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::put('/orders/${orderId}/cancel', [OrderController::class, 'cancel']);
        Route::post('/payments', [PaymentController::class, 'store']);
        
        Route::apiResource('favorites', FavoriteController::class)->only([
            'index', 'store', 
        ]);
        Route::delete('favorites/{productId}', [FavoriteController::class, 'destroy']);
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/items', [CartController::class, 'store']);
        Route::put('cart/items/{cartItem}', [CartController::class, 'update']);
        Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy']);
    });

    // Защищенные админские роуты
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/user', [AdminAuthController::class, 'user']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::apiResource('users', AdminUserController::class);
        Route::apiResource('products', AdminProductController::class);
        Route::apiResource('categories', AdminCategoryController::class);
        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        Route::post('products/{product}/upload-image', [ProductController::class, 'uploadImage']);
        Route::post('products/upload-image-new', [AdminProductController::class, 'uploadImageForNewProduct']);
        Route::apiResource('collections', AdminCollectionController::class);
        Route::post('users/upload-avatar', [AdminUserController::class, 'uploadAvatar']);
        Route::post('users/{user}/upload-avatar', [AdminUserController::class, 'uploadAvatar']);
        Route::get('/products/stats', [AdminProductController::class, 'stats']);
    });
});
