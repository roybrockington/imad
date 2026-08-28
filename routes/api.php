<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CareerController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Handle CORS preflight for all API routes
Route::options('/{any}', function () {
    return response('', 200);
})->where('any', '.*');

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Product management routes
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/csv-import', [ProductController::class, 'processCsvImport']);

    // User management routes (admin only)
    Route::get('/users/pending', [UserController::class, 'pendingApprovals']);
    Route::post('/users/{user}/approve', [UserController::class, 'approve']);
    Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
    Route::put('/users/{user}/account', [UserController::class, 'updateAccount']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::get('/users', [UserController::class, 'index']);

    // Account management routes (admin only)
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{account}', [AccountController::class, 'show']);

    // Region routes (admin only)
    Route::get('/regions', [RegionController::class, 'index']);

    // Address routes
    Route::get('/addresses', [AddressController::class, 'index']);

    // Dashboard stats route (admin only)
    Route::get('/admin/stats', [DashboardController::class, 'stats']);

    // Brand management routes (admin only)
    Route::get('/admin/brands', [BrandController::class, 'adminIndex']);

    // Order routes
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/user/orders', [OrderController::class, 'userOrders']);

    // Admin/Staff order management routes
    Route::middleware('admin.staff')->group(function () {
        Route::get('/admin/orders', [OrderController::class, 'index']);
        Route::patch('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);

        // Order export queue routes
        Route::get('/admin/order-exports', [\App\Http\Controllers\Api\Admin\OrderExportController::class, 'index']);
        Route::get('/admin/order-exports/statistics', [\App\Http\Controllers\Api\Admin\OrderExportController::class, 'statistics']);
        Route::post('/admin/order-exports/mark-exported', [\App\Http\Controllers\Api\Admin\OrderExportController::class, 'markAsExported']);
        Route::post('/admin/order-exports/export-to-sage', [\App\Http\Controllers\Api\Admin\OrderExportController::class, 'exportToSage']);
    });

    // Admin article management routes
    Route::prefix('admin/articles')->group(function () {
        Route::get('/', [ArticleController::class, 'adminIndex']);
        Route::post('/', [ArticleController::class, 'store']);
        Route::get('/{id}', [ArticleController::class, 'adminShow']);
        Route::put('/{id}', [ArticleController::class, 'update']);
        Route::delete('/{id}', [ArticleController::class, 'destroy']);
        Route::post('/upload-image', [ArticleController::class, 'uploadImage']);
    });

    // Admin career management routes
    Route::prefix('admin/careers')->group(function () {
        Route::get('/', [CareerController::class, 'adminIndex']);
        Route::post('/', [CareerController::class, 'store']);
        Route::get('/{career}', [CareerController::class, 'show']);
        Route::put('/{career}', [CareerController::class, 'update']);
        Route::delete('/{career}', [CareerController::class, 'destroy']);
    });

    // Admin slide management routes
    Route::prefix('admin/slides')->group(function () {
        Route::get('/', [SlideController::class, 'adminIndex']);
        Route::post('/', [SlideController::class, 'store']);
        Route::get('/{slide}', [SlideController::class, 'show']);
        Route::put('/{slide}', [SlideController::class, 'update']);
        Route::delete('/{slide}', [SlideController::class, 'destroy']);
        Route::post('/reorder', [SlideController::class, 'reorder']);
    });
});

// Public product routes (read-only)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Public brand routes (read-only)
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/territories', [BrandController::class, 'territories']);
Route::get('/brands/{brandName}/{productName}/{variantSlug}', [ProductController::class, 'showByBrandProductAndVariant']);
Route::get('/brands/{brandName}/{productName}', [ProductController::class, 'showByBrandAndName']);
Route::get('/brands/{name}', [BrandController::class, 'show']);

// Public article routes (read-only)
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);

// Public career routes (read-only)
Route::get('/careers', [CareerController::class, 'index']);

// Public slide routes (read-only)
Route::get('/slides', [SlideController::class, 'index']);

// Public country routes (read-only)
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{code}', [CountryController::class, 'show']);

// Artikel lookup endpoint for redirects
Route::get('/artikel/{article}', [ProductController::class, 'getByArticle']);
