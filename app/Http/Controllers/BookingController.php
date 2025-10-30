<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
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
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at,
                    'items' => $booking->items->map(function ($item) {
                        return [
                            'movie_id' => $item->movie_id,
                            'movie_title' => $item->movie_title,
                            'date' => $item->booking_date,
                            'time' => date('H:i', strtotime($item->showtime)),
                            'cinema' => $item->cinema,
                            'seats' => [$item->seat_key],
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
            return response()->json([
                'error' => 'Authentication required',
            ], 401);
        }

        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.movieId' => 'required|string',
                'items.*.title' => 'required|string',
                'items.*.date' => 'required|date',
                'items.*.time' => 'required|string',
                'items.*.cinema' => 'required|string',
                'items.*.seats' => 'required|array|min:1',
                'items.*.seatPrice' => 'required|numeric',
            ]);

            $user = Auth::user();
            $total = 0;
            $bookingItems = [];

            DB::beginTransaction();

            // Check seat availability and prepare items
            foreach ($validated['items'] as $item) {
                foreach ($item['seats'] as $seatKey) {
                    // Check if seat is already occupied
                    $existing = BookingItem::whereHas('booking', function ($query) {
                        $query->where('status', '!=', 'Cancelled');
                    })
                        ->where('movie_id', $item['movieId'])
                        ->where('booking_date', $item['date'])
                        ->where('showtime', $item['time'])
                        ->where('cinema', $item['cinema'])
                        ->where('seat_key', $seatKey)
                        ->exists();

                    if ($existing) {
                        DB::rollBack();
                        return response()->json([
                            'error' => "Seat {$seatKey} is already reserved for this session",
                        ], 400);
                    }

                    $seatParts = explode('-', $seatKey);
                    $bookingItems[] = [
                        'movieId' => $item['movieId'],
                        'title' => $item['title'],
                        'date' => $item['date'],
                        'time' => $item['time'],
                        'cinema' => $item['cinema'],
                        'row' => $seatParts[0],
                        'col' => $seatParts[1],
                        'seatKey' => $seatKey,
                        'price' => $item['seatPrice'],
                    ];

                    $total += $item['seatPrice'];
                }
            }

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '',
                'reference' => '',
                'total' => $total,
                'status' => 'Paid',
            ]);

            // Insert booking items
            foreach ($bookingItems as $item) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'movie_id' => $item['movieId'],
                    'movie_title' => $item['title'],
                    'booking_date' => $item['date'],
                    'showtime' => $item['time'],
                    'cinema' => $item['cinema'],
                    'seat_row' => $item['row'],
                    'seat_col' => $item['col'],
                    'seat_key' => $item['seatKey'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'bookingId' => $booking->id,
                'message' => 'Booking confirmed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create booking',
            ], 500);
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
}
