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
    });
    
    // Redirect /admin to dashboard
    Route::redirect('/', '/admin/dashboard');
});
