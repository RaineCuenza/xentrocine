<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SeatController;

/*
|--------------------------------------------------------------------------
| Web Routes - XentroCine
|--------------------------------------------------------------------------
*/

// Page Routes (will serve Blade templates)
Route::get('/', function () {
    return view('index');
})->name('home');

Route::get('/booking', function () {
    return view('booking');
})->name('booking');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/admin', function () {
    return view('admin');
})->name('admin');

Route::get('/about', function () {
    return view('about');
})->name('about');

// Email Verification Route (outside API prefix for cleaner URLs)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

// API Routes (JSON responses for AJAX calls)
Route::prefix('api')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/signup', [AuthController::class, 'signup']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/session', [AuthController::class, 'session']);
        Route::post('/email/resend', [AuthController::class, 'resendVerification'])
            ->middleware('auth')
            ->name('verification.resend');
    });

    // Movie routes
    Route::prefix('movies')->group(function () {
        Route::get('/', [MovieController::class, 'index']);
        Route::get('/{id}', [MovieController::class, 'show']);
        Route::post('/', [MovieController::class, 'store']);
        Route::put('/{id}', [MovieController::class, 'update']);
        Route::delete('/{id}', [MovieController::class, 'destroy']);
    });

    // Booking routes
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
        Route::delete('/reset', [BookingController::class, 'reset']);
    });

    // Seat availability routes
    Route::prefix('seats')->group(function () {
        Route::get('/occupied', [SeatController::class, 'getOccupied']);
        Route::post('/check', [SeatController::class, 'checkAvailability']);
    });

    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'XentroCine API is running',
            'timestamp' => now()->toISOString(),
        ]);
    });
});
