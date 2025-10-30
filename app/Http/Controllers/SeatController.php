<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BookingItem;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    /**
     * Get occupied seats for a specific session
     */
    public function getOccupied(Request $request)
    {
        try {
            $validated = $request->validate([
                'movieId' => 'required|string',
                'date' => 'required|date',
                'time' => 'required|string',
                'cinema' => 'required|string',
            ]);

            // Get occupied seats from non-cancelled bookings
            $occupiedSeats = BookingItem::whereHas('booking', function ($query) {
                $query->where('status', '!=', 'Cancelled');
            })
                ->where('movie_id', $validated['movieId'])
                ->where('booking_date', $validated['date'])
                ->where('showtime', $validated['time'])
                ->where('cinema', $validated['cinema'])
                ->pluck('seat_key')
                ->toArray();

            $sessionId = "{$validated['movieId']}|{$validated['date']}|{$validated['time']}|{$validated['cinema']}";

            return response()->json([
                'sessionId' => $sessionId,
                'occupiedSeats' => $occupiedSeats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch occupied seats',
            ], 500);
        }
    }

    /**
     * Check if specific seats are available
     */
    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'movieId' => 'required|string',
                'date' => 'required|date',
                'time' => 'required|string',
                'cinema' => 'required|string',
                'seats' => 'required|array|min:1',
                'seats.*' => 'required|string',
            ]);

            $unavailableSeats = [];

            foreach ($validated['seats'] as $seatKey) {
                $exists = BookingItem::whereHas('booking', function ($query) {
                    $query->where('status', '!=', 'Cancelled');
                })
                    ->where('movie_id', $validated['movieId'])
                    ->where('booking_date', $validated['date'])
                    ->where('showtime', $validated['time'])
                    ->where('cinema', $validated['cinema'])
                    ->where('seat_key', $seatKey)
                    ->exists();

                if ($exists) {
                    $unavailableSeats[] = $seatKey;
                }
            }

            return response()->json([
                'available' => count($unavailableSeats) === 0,
                'unavailableSeats' => $unavailableSeats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check seat availability',
            ], 500);
        }
    }
}
