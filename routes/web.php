<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CartController;
use App\Models\Cinema;

/*
|--------------------------------------------------------------------------
| Web Routes - XentroCine
|--------------------------------------------------------------------------
*/

// Page Routes (serve Blade templates with data from controllers)
Route::get('/', [PageController::class, 'index'])->name('home');
Route::middleware(['auth', \App\Http\Middleware\NotAdmin::class])->group(function () {
    Route::get('/booking', [PageController::class, 'booking'])->name('booking');
    Route::get('/my-bookings', [PageController::class, 'myBookings'])->name('my-bookings');
    // Web Cart routes (form posts)
    Route::post('/cart/add', [CartController::class, 'store'])->name('cart.add');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/{index}/delete', [CartController::class, 'destroy'])->name('cart.remove');
    // Web Booking checkout
    Route::post('/bookings/place', [BookingController::class, 'store'])->name('bookings.place');
    // Booking cancellation
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
});
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/forgot-password', [PageController::class, 'forgotPassword'])->name('password.request');
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
// Password reset (web forms)
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'doReset'])->name('password.update');

// Move cart and booking routes into not.admin group above

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
        Route::get('/showing', [MovieController::class, 'showingNow']);
        Route::get('/{id}', [MovieController::class, 'show']);
        Route::get('/{id}/showtimes/today', [MovieController::class, 'todayShowtimes']);
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
    Route::post('/', [BookingController::class, 'store'])->middleware(['auth', \App\Http\Middleware\NotAdmin::class]);
        Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
        Route::delete('/reset', [BookingController::class, 'reset']);
        Route::post('/scan', [BookingController::class, 'scan'])->middleware(['auth', \App\Http\Middleware\NotAdmin::class]);
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
        if (!Auth::check() || (Auth::user()->role ?? 'client') !== 'admin') {
            return redirect()->route('home')->with('error', 'Unauthorized');
        }
        $validated = $request->validate([
            'title' => 'required|string',
            'price' => 'required|numeric|min:0',
            'poster' => 'nullable|string',
            'audience' => 'required|in:G,PG,R-13,R-16,R-18',
            'times' => 'required|string',
            // New listing window fields
            'listing_start' => 'required|date',
            'listing_end' => 'required|date|after_or_equal:listing_start',
        ]);

        // Enforce a maximum of 30 days run from start to end
        $start = \Carbon\Carbon::parse($validated['listing_start']);
        $end = \Carbon\Carbon::parse($validated['listing_end']);
        if ($end->gt($start->copy()->addDays(30))) {
            return back()->withErrors([
                'listing_end' => 'A movie can only run for a maximum of 1 month from its start date.'
            ])->withInput();
        }
        // Use API controller logic but via models directly to avoid JSON
        $movie = App\Models\Movie::create([
            'title' => trim($validated['title']),
            'price' => $validated['price'],
            'poster' => $validated['poster'] ?? null,
            'audience' => $validated['audience'],
            'listing_start' => $start->toDateString(),
            'listing_end' => $end->toDateString(),
        ]);
        foreach (array_filter(array_map('trim', explode(',', $validated['times']))) as $time) {
            App\Models\Showtime::create(['movie_id' => $movie->id, 'showtime' => $time]);
        }
        return redirect()->route('admin')->with('message', 'Movie added');
    });

    Route::post('/admin/movies/{id}/status', function (\Illuminate\Http\Request $request, $id) {
        if (!Auth::check() || (Auth::user()->role ?? 'client') !== 'admin') {
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

    // Remove hard delete in favor of archive/unarchive via status route above

    Route::post('/admin/bookings/{id}/cancel', function ($id) {
        if (!Auth::check() || (Auth::user()->role ?? 'client') !== 'admin') {
            return redirect()->route('home')->with('error', 'Unauthorized');
        }
        $booking = App\Models\Booking::find($id);
        if ($booking && $booking->status !== 'Cancelled') {
            $booking->update(['status' => 'Cancelled']);
        }
        return redirect()->route('admin')->with('message', 'Booking cancelled');
    });

    Route::post('/admin/cinemas/{id}', function (\Illuminate\Http\Request $request, $id) {
        Log::info('[Cinema Update] Received request', [
            'cinema_id' => $id,
            'status' => $request->input('status'),
            'capacity' => $request->input('capacity'),
        ]);

        if (!Auth::check() || (Auth::user()->role ?? 'client') !== 'admin') {
            $error = ['error' => 'Unauthorized'];
            Log::warning('[Cinema Update] Unauthorized attempt', ['cinema_id' => $id]);
            return $request->expectsJson()
                ? response()->json($error, 403)
                : redirect()->route('home')->with('error', 'Unauthorized');
        }

        /** @var \App\Models\Cinema|null $cinema */
        $cinema = Cinema::find($id);
        if (!$cinema) {
            $error = ['error' => 'Cinema not found'];
            Log::error('[Cinema Update] Cinema not found', ['cinema_id' => $id]);
            return $request->expectsJson()
                ? response()->json($error, 404)
                : redirect()->route('admin')->with('error', 'Cinema not found');
        }

        $validated = $request->validate([
            'status' => 'required|in:available,maintenance,unavailable,inactive',
            'capacity' => 'nullable|integer|min:1|max:500',
        ]);

        $cinema->status = $validated['status'];
        if (array_key_exists('capacity', $validated) && $validated['capacity'] !== null) {
            $cinema->capacity = (int) $validated['capacity'];
        }
        $cinema->save();

        Log::info('[Cinema Update] Successfully updated', [
            'cinema_id' => $cinema->id,
            'name' => $cinema->name,
            'status' => $cinema->status,
            'capacity' => $cinema->capacity,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cinema' => [
                    'id' => $cinema->id,
                    'name' => $cinema->name,
                    'status' => $cinema->status,
                    'capacity' => (int) $cinema->capacity,
                ],
                'message' => 'Cinema updated',
            ]);
        }

        return redirect()->route('admin')->with('message', 'Cinema updated successfully!');
    });
    
    // API: List cinemas (optional status filter). Used by client booking UI.
    Route::get('/api/cinemas', function (\Illuminate\Http\Request $request) {
        $status = $request->query('status');
        $query = Cinema::query()->orderBy('name');
        if ($status && in_array($status, ['available','maintenance','unavailable','inactive'])) {
            $query->where('status', $status);
        }
        $cinemas = $query->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'capacity' => (int) ($c->capacity ?? 120),
                'status' => $c->status ?? 'available',
            ];
        });
        return response()->json($cinemas);
    });
});

// Local-only test mail route to verify SMTP connectivity
if (app()->environment('local')) {
    Route::get('/dev/test-mail', function () {
        $to = env('MAIL_TEST_TO', 'to@example.com');
        try {
            Mail::raw('XentroCine test email: If you see this in Mailtrap, SMTP is working.', function ($message) use ($to) {
                $message->to($to)->subject('XentroCine SMTP Test');
            });
            return response('Test mail dispatched to '.$to, 200);
        } catch (\Throwable $e) {
            return response('Mail error: '.$e->getMessage(), 500);
        }
    })->name('dev.test-mail');
}
