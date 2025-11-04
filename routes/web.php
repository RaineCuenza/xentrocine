<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CartController;

/*
|--------------------------------------------------------------------------
| Web Routes - XentroCine
|--------------------------------------------------------------------------
*/

// Page Routes (serve Blade templates with data from controllers)
Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/booking', [PageController::class, 'booking'])->name('booking');
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/signup', [PageController::class, 'signup'])->name('signup');
Route::get('/admin', [PageController::class, 'admin'])->name('admin');
Route::get('/staff', [PageController::class, 'staff'])->name('staff');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/qr-test', [PageController::class, 'qrTest'])->name('qr-test');

// Email Verification Route (outside API prefix for cleaner URLs)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

// Web Auth (non-API) routes for forms
Route::post('/login', [AuthController::class, 'webLogin'])->name('auth.login.submit');
Route::post('/signup', [AuthController::class, 'webSignup'])->name('auth.signup.submit');
Route::post('/logout', [AuthController::class, 'webLogout'])->name('auth.logout');

// Web Cart routes (form posts)
Route::post('/cart/add', [CartController::class, 'store'])->name('cart.add');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/{index}/delete', [CartController::class, 'destroy'])->name('cart.remove');

// Web Booking checkout
Route::post('/bookings/place', [BookingController::class, 'store'])->name('bookings.place');

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

    // Cart routes (session-based)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::delete('/{index}', [CartController::class, 'destroy']);
        Route::post('/clear', [CartController::class, 'clear']);
    });

    // Booking routes
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
        Route::delete('/reset', [BookingController::class, 'reset']);
        Route::post('/scan', [BookingController::class, 'scan']);
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

// Admin web actions (forms) - simple wrappers
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/movies', function (\Illuminate\Http\Request $request) {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Unauthorized');
        }
        $validated = $request->validate([
            'title' => 'required|string',
            'price' => 'required|numeric|min:0',
            'poster' => 'nullable|string',
            'audience' => 'required|in:G,PG,R-13,R-16,R-18',
            'times' => 'required|string',
        ]);
        // Use API controller logic but via models directly to avoid JSON
        $movie = App\Models\Movie::create([
            'title' => trim($validated['title']),
            'price' => $validated['price'],
            'poster' => $validated['poster'] ?? null,
            'audience' => $validated['audience'],
        ]);
        foreach (array_filter(array_map('trim', explode(',', $validated['times']))) as $time) {
            App\Models\Showtime::create(['movie_id' => $movie->id, 'showtime' => $time]);
        }
        return redirect()->route('admin')->with('message', 'Movie added');
    });

    Route::post('/admin/movies/{id}/status', function (\Illuminate\Http\Request $request, $id) {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            $error = ['error' => 'Unauthorized'];
            return $request->expectsJson()
                ? response()->json($error, 403)
                : redirect()->route('home')->with('error', 'Unauthorized');
        }
        $movie = App\Models\Movie::find($id);
        if (!$movie) {
            $error = ['error' => 'Movie not found'];
            return $request->expectsJson()
                ? response()->json($error, 404)
                : redirect()->route('admin')->with('error', 'Movie not found');
        }

        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $movie->update([
            'is_active' => $validated['status'] === 'active',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $movie->is_active,
                'message' => 'Movie status updated',
            ]);
        }

        return redirect()->route('admin')->with('message', 'Movie status updated');
    });

    Route::delete('/admin/movies/{id}', function (\Illuminate\Http\Request $request, $id) {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            $error = ['error' => 'Unauthorized'];
            return $request->expectsJson()
                ? response()->json($error, 403)
                : redirect()->route('home')->with('error', 'Unauthorized');
        }
        $movie = App\Models\Movie::find($id);
        if (!$movie) {
            $error = ['error' => 'Movie not found'];
            return $request->expectsJson()
                ? response()->json($error, 404)
                : redirect()->route('admin')->with('error', 'Movie not found');
        }

        $movie->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Movie deleted',
            ]);
        }

        return redirect()->route('admin')->with('message', 'Movie deleted');
    });

    Route::post('/admin/bookings/{id}/cancel', function ($id) {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Unauthorized');
        }
        $booking = App\Models\Booking::find($id);
        if ($booking && $booking->status !== 'Cancelled') {
            $booking->update(['status' => 'Cancelled']);
        }
        return redirect()->route('admin')->with('message', 'Booking cancelled');
    });
});
