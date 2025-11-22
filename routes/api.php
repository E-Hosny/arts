<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

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
    // TODO: Will be implemented in next parts

    // Artwork Routes
    // TODO: Will be implemented in next parts

    // Order Routes
    // TODO: Will be implemented in next parts

    // Admin Routes
    // TODO: Will be implemented in next parts
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
