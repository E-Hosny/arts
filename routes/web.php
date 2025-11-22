<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AdminController;

Route::get('/', function () {
    return view('welcome');
});

// Admin Web Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Login routes (guest only)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminController::class, 'login']);
    });
    
    // Protected admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        
        // Artists management
        Route::prefix('artists')->name('artists.')->group(function () {
            Route::get('/pending', [AdminController::class, 'pendingArtists'])->name('pending');
            Route::get('/{artist}/review', [AdminController::class, 'reviewArtist'])->name('review');
            Route::post('/{artist}/approve', [AdminController::class, 'approveArtist'])->name('approve');
            Route::post('/{artist}/reject', [AdminController::class, 'rejectArtist'])->name('reject');
        });

        // Artworks management
        Route::prefix('artworks')->name('artworks.')->group(function () {
            Route::get('/', [AdminController::class, 'artworksIndex'])->name('index');
            Route::get('/{artwork}', [AdminController::class, 'artworkShow'])->name('show');
            Route::put('/{artwork}/status', [AdminController::class, 'artworkUpdateStatus'])->name('update-status');
        });

        // Orders management
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [AdminController::class, 'ordersIndex'])->name('index');
            Route::get('/{order}', [AdminController::class, 'orderShow'])->name('show');
            Route::post('/{order}/ship', [AdminController::class, 'orderShip'])->name('ship');
            Route::post('/{order}/deliver', [AdminController::class, 'orderDeliver'])->name('deliver');
        });
    });
    
    // Redirect /admin to dashboard
    Route::redirect('/', '/admin/dashboard');
});
