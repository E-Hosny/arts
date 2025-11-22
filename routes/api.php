<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Admin\ArtistReviewController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\Artist\ArtworkController as ArtistArtworkController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderManagementController;

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

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public Artist Routes
Route::prefix('artists')->group(function () {
    Route::post('/register', [ArtistController::class, 'register']);
});

// Public Artwork Routes
Route::prefix('artworks')->group(function () {
    Route::get('/', [ArtworkController::class, 'index']);
    Route::get('/categories', [ArtworkController::class, 'categories']);
    Route::get('/featured', [ArtworkController::class, 'featured']);
    Route::get('/search/artist', [ArtworkController::class, 'searchByArtist']);
    Route::get('/{artwork}', [ArtworkController::class, 'show']);
});

// Protected Routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // User Profile Routes
    // TODO: Will be implemented in next parts

    // Artist Routes
    Route::prefix('artists')->group(function () {
        Route::get('/status', [ArtistController::class, 'status']);
    });

    // Artist Artwork Management (requires approved artist)
    Route::middleware('approved.artist')->prefix('artist')->group(function () {
        Route::apiResource('artworks', ArtistArtworkController::class);
        
        // Artist Order Management
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderManagementController::class, 'index']);
            Route::get('/{order}', [OrderManagementController::class, 'show']);
            Route::put('/{order}/ship', [OrderManagementController::class, 'ship']);
            Route::put('/{order}/deliver', [OrderManagementController::class, 'deliver']);
        });
    });

    // Buyer Order Routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
    });

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::prefix('artists')->group(function () {
            Route::get('/pending', [ArtistReviewController::class, 'indexPending']);
            Route::get('/{artist}', [ArtistReviewController::class, 'show']);
            Route::post('/{artist}/approve', [ArtistReviewController::class, 'approve']);
            Route::post('/{artist}/reject', [ArtistReviewController::class, 'reject']);
        });
        
        // Admin Order Management
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderManagementController::class, 'index']);
            Route::get('/{order}', [OrderManagementController::class, 'show']);
            Route::put('/{order}/ship', [OrderManagementController::class, 'ship']);
            Route::put('/{order}/deliver', [OrderManagementController::class, 'deliver']);
        });
    });
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'المسار المطلوب غير موجود',
        'errors' => [
            'route' => ['المسار المطلوب غير صحيح أو غير موجود'],
        ],
    ], 404);
});
