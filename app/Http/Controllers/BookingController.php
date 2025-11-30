<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Mail\BookingConfirmation;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }
    /**
     * Get user's bookings or all bookings (admin)
     */
    public function index()
    {
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Authentication required',
            ], 401);
        }

        try {
            $user = Auth::user();
            
            $bookingsQuery = Booking::with('items');

            if ($user->role !== 'admin') {
                // Users see only their bookings
                $bookingsQuery->where('user_id', $user->id);
            }

            $bookings = $bookingsQuery->orderBy('created_at', 'desc')->get();

            $result = $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'name' => $booking->name,
                    'email' => $booking->email,
                    'phone' => $booking->phone,
                    'reference' => $booking->reference,
                    'total' => (float) $booking->total,
                    'status' => $booking->status,
                    // Expose token for the owner/admin to support QR test page and manual entry
                    'et_qr_token' => $booking->et_qr_token,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at,
                    'items' => $booking->items->map(function ($item) {
                        return [
                            'movie_id' => $item->movie_id,
                            'movie_title' => $item->movie_title,
                            'date' => $item->booking_date,
                            'time' => date('H:i', strtotime($item->showtime)),
                            'cinema' => $item->cinema,
                            'quantity' => (int) $item->quantity,
                            'price' => (float) $item->price,
                        ];
                    })->toArray(),
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch bookings',
            ], 500);
        }
    }

    /**
     * Create new booking
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Authentication required',
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if email is verified
        if (is_null($user->email_verified_at)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Please verify your email before making a booking',
                    'requires_verification' => true,
                ], 403);
            }
            return back()->withErrors(['booking' => 'Please verify your email before booking.']);
        }

        try {
            // For web requests without explicit items, use session cart
            $usingSessionCart = !$request->expectsJson() && !$request->has('items');
            if ($usingSessionCart) {
                $validated = $request->validate([
                    'paymentMethod' => 'nullable|in:cash,digital',
                ]);
                $cartItems = $this->cartService->getItems();
                if (count($cartItems) === 0) {
                    return back()->withErrors(['booking' => 'Cart is empty']);
                }
            } else {
                $validated = $request->validate([
                    'paymentMethod' => 'nullable|in:cash,digital',
                    'items' => 'required|array|min:1',
                    'items.*.movieId' => 'required|string',
                    'items.*.title' => 'required|string',
                    'items.*.date' => 'required|date|after_or_equal:today',
                    'items.*.time' => 'required|string',
                    'items.*.cinema' => 'required|string',
                    'items.*.quantity' => 'required|integer|min:1',
                    'items.*.seatPrice' => 'required|numeric',
                ]);
                $cartItems = $validated['items'];
            }

            $user = Auth::user();
            $paymentMethod = $validated['paymentMethod'] ?? 'digital';
            $total = 0;
            $bookingItems = [];

            DB::beginTransaction();

            // Prepare pax-based items
            foreach ($cartItems as $item) {
                $qty = (int) $item['quantity'];
                $bookingItems[] = [
                    'movieId' => $item['movieId'],
                    'title' => $item['title'],
                    'date' => $item['date'],
                    'time' => $item['time'],
                    'cinema' => $item['cinema'],
                    'quantity' => $qty,
                    'price' => $item['seatPrice'],
                ];
                $total += ($item['seatPrice'] * $qty);
            }

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '',
                'reference' => '',
                'total' => $total,
                'status' => $paymentMethod === 'cash' ? 'Pending' : 'Paid',
                'payment_method' => $paymentMethod,
                'et_qr_token' => 'et_' . Str::random(40),
            ]);

            // Insert booking items (one row per session with quantity)
            foreach ($bookingItems as $item) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'movie_id' => $item['movieId'],
                    'movie_title' => $item['title'],
                    'booking_date' => $item['date'],
                    'showtime' => $item['time'],
                    'cinema' => $item['cinema'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();

            // Clear the cart after successful booking
            $this->cartService->clear();

            // Send e-ticket email with QR code
            try {
                Mail::to($booking->email)->send(new BookingConfirmation($booking->load('items')));
            } catch (\Exception $e) {
                // Log email error but don't fail the booking
                Log::error('Failed to send booking confirmation email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'bookingId' => $booking->id,
                    'message' => 'Booking confirmed successfully',
                ]);
            }
            return redirect()->route('booking')->with('message', 'Booking confirmed!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            // Check if it's a date validation error
            $errors = $e->errors();
            if (isset($errors['items.0.date'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Cannot book tickets for past dates. Please select today or a future date.',
                    ], 400);
                }
                return back()->withErrors(['booking' => 'Cannot book tickets for past dates.'])->withInput();
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Invalid booking data: ' . $e->getMessage(),
                ], 400);
            }
            return back()->withErrors(['booking' => 'Invalid booking data'])->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to create booking',
                ], 500);
            }
            return back()->withErrors(['booking' => 'Failed to create booking']);
        }
    }

    /**
     * Cancel a booking
     */
    public function cancel($id)
    {
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Authentication required',
            ], 401);
        }

        try {
            $booking = Booking::find($id);

            if (!$booking) {
                return response()->json([
                    'error' => 'Booking not found',
                ], 404);
            }

            $user = Auth::user();

            if ($user->role !== 'admin' && $booking->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Access denied',
                ], 403);
            }

            if ($booking->status === 'Cancelled') {
                return response()->json([
                    'error' => 'Booking already cancelled',
                ], 400);
            }

            $booking->update(['status' => 'Cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel booking',
            ], 500);
        }
    }

    /**
     * Reset all bookings (admin only, for demo)
     */
    public function reset()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'Admin access required',
            ], 403);
        }

        try {
            BookingItem::truncate();
            Booking::truncate();

            return response()->json([
                'success' => true,
                'message' => 'All bookings reset successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reset bookings',
            ], 500);
        }
    }

    /**
     * Scan QR code for check-in or payment verification
     */
    public function scan(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Authentication required',
            ], 401);
        }

        $user = Auth::user();
        
        // Only admin and staff can scan
        if (!in_array($user->role, ['admin', 'staff'])) {
            return response()->json([
                'error' => 'Access denied. Staff or admin access required.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'token' => 'required|string',
                'confirmCash' => 'nullable|boolean',
                'confirmCheckIn' => 'nullable|boolean',
            ]);

            $token = $validated['token'];
            $confirmCash = $validated['confirmCash'] ?? false;
            $confirmCheckIn = $validated['confirmCheckIn'] ?? false;

            // Find booking by entry token
            $booking = Booking::where('et_qr_token', $token)->first();

            if (!$booking) {
                return response()->json([
                    'error' => 'Invalid ticket. QR code not found.',
                ], 404);
            }

            // Check if cancelled
            if ($booking->isCancelled()) {
                return response()->json([
                    'error' => 'Booking is cancelled.',
                ], 400);
            }

            // Check if already checked in
            if ($booking->isCheckedIn()) {
                return response()->json([
                    'error' => 'Ticket already checked in.',
                    'checked_in_at' => $booking->entry_scanned_at->format('Y-m-d H:i:s'),
                ], 409);
            }

            // If no confirmation flags were sent, return info-only response
            if (!$confirmCheckIn && !$confirmCash) {
                $firstItem = $booking->items->first();

                // Paid bookings require staff verification
                if ($booking->status === 'Paid') {
                    return response()->json([
                        'awaiting_checkin' => true,
                        'message' => 'Paid booking. Tap Verify Entry to check-in.',
                        'booking' => [
                            'id' => $booking->id,
                            'name' => $booking->name,
                            'movie' => $firstItem ? $firstItem->movie_title : '',
                            'date' => $firstItem ? \Illuminate\Support\Carbon::parse($firstItem->booking_date)->format('M d, Y') : '',
                            'time' => $firstItem ? date('g:i A', strtotime($firstItem->showtime)) : '',
                            'cinema' => $firstItem ? $firstItem->cinema : '',
                            'pax' => (int) $booking->items->sum('quantity'),
                            'total' => (float) $booking->total,
                            'payment_method' => $booking->payment_method,
                            'status' => $booking->status,
                        ],
                    ]);
                }

                // Pending (cash) bookings need cash confirmation first
                $firstItem = $booking->items->first();
                return response()->json([
                    'pending_payment' => true,
                    'message' => 'Cash payment required. Please accept payment and confirm.',
                    'booking' => [
                        'id' => $booking->id,
                        'name' => $booking->name,
                        'movie' => $firstItem ? $firstItem->movie_title : '',
                        'date' => $firstItem ? \Illuminate\Support\Carbon::parse($firstItem->booking_date)->format('M d, Y') : '',
                        'time' => $firstItem ? date('g:i A', strtotime($firstItem->showtime)) : '',
                        'cinema' => $firstItem ? $firstItem->cinema : '',
                        'pax' => (int) $booking->items->sum('quantity'),
                        'total' => (float) $booking->total,
                        'payment_method' => $booking->payment_method,
                        'status' => $booking->status,
                    ],
                ]);
            }

            // If we reach here, a confirmation was requested
            if ($booking->status === 'Pending') {
                if (!$confirmCash) {
                    return response()->json([
                        'error' => 'Cash confirmation required to check-in.',
                    ], 400);
                }
                // Accept cash first
                $booking->status = 'Paid';
            }

            // Perform check-in
            if ($confirmCheckIn || $booking->status === 'Paid') {
                $booking->entry_scanned_at = now();
                $booking->checked_in_by = $user->id;
                $booking->save();

                $firstItem = $booking->items->first();
                return response()->json([
                    'success' => true,
                    'message' => 'Check-in successful!',
                    'booking' => [
                        'id' => $booking->id,
                        'name' => $booking->name,
                        'movie' => $firstItem ? $firstItem->movie_title : '',
                        'date' => $firstItem ? \Illuminate\Support\Carbon::parse($firstItem->booking_date)->format('M d, Y') : '',
                        'time' => $firstItem ? date('g:i A', strtotime($firstItem->showtime)) : '',
                        'cinema' => $firstItem ? $firstItem->cinema : '',
                        'pax' => (int) $booking->items->sum('quantity'),
                        'total' => (float) $booking->total,
                        'payment_method' => $booking->payment_method,
                        'status' => $booking->status,
                        'checked_in_at' => $booking->entry_scanned_at->format('Y-m-d H:i:s'),
                    ],
                ]);
            }

            // Fallback (shouldn't normally hit)
            return response()->json([
                'error' => 'Unable to process request.',
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Invalid request data',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process scan',
            ], 500);
        }
    }
}
