<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get cart items
     */
    public function index()
    {
        return response()->json([
            'items' => $this->cartService->getItems(),
            'count' => $this->cartService->getCount(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    /**
     * Add item to cart
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

        // Block admin users from creating reservations via cart
        if (Auth::user()->role === 'admin') {
            abort(403, 'Admins are not allowed to make bookings.');
        }

        try {
            // Find movie to enforce listing window
            $movieId = $request->input('movieId');
            $movie = $movieId ? \App\Models\Movie::find($movieId) : null;

            // Build date rules based on listing window
            $dateRules = ['required', 'date'];
            if ($movie) {
                $start = $movie->listing_start ? \Carbon\Carbon::parse($movie->listing_start)->toDateString() : now()->toDateString();
                $dateRules[] = 'after_or_equal:' . $start;
                if ($movie->listing_end) {
                    $end = \Carbon\Carbon::parse($movie->listing_end)->toDateString();
                    $dateRules[] = 'before_or_equal:' . $end;
                } else {
                    // No explicit end: cap at 30 days after start
                    $cap = \Carbon\Carbon::parse($start)->addDays(30)->toDateString();
                    $dateRules[] = 'before_or_equal:' . $cap;
                }
            } else {
                // Fallback
                $dateRules[] = 'after_or_equal:today';
            }

            $validated = $request->validate([
                'movieId' => 'required|string',
                'title' => 'required|string',
                'show_date' => $dateRules,
                'time' => 'sometimes|string',
                'showtimeId' => 'sometimes|integer',
                'cinema' => 'required|string',
                'quantity' => 'required|integer|min:1|max:20',
                'seatPrice' => 'required|numeric|min:0',
            ]);

            // Resolve showtime ID to start time if provided
            if (empty($validated['time']) && !empty($validated['showtimeId'])) {
                $st = \App\Models\Showtime::find($validated['showtimeId']);
                if (!$st) {
                    throw new \Exception('Invalid showtime selected');
                }
                $validated['time'] = date('H:i', strtotime($st->showtime));
            }

            // Normalize to existing cart item shape
            $validated['date'] = $validated['show_date'];

            // Enforce cinema capacity for the session
            try {
                $capacityMap = (array) config('app.cinemas', ['Cinema 1' => 120, 'Cinema 2' => 120]);
                $cinema = $validated['cinema'];
                $capacity = (int) ($capacityMap[$cinema] ?? 120);

                $movieId = $validated['movieId'];
                $date = $validated['date'];
                $timeNorm = date('H:i:s', strtotime($validated['time']));

                // Current booked in DB (exclude cancelled)
                $booked = \App\Models\BookingItem::where('movie_id', $movieId)
                    ->whereDate('booking_date', $date)
                    ->where('showtime', $timeNorm)
                    ->where('cinema', $cinema)
                    ->whereHas('booking', function ($b) { $b->where('status', '!=', 'Cancelled'); })
                    ->sum('quantity');

                // Items already in cart for same session
                $cartItems = $this->cartService->getItems();
                $cartQty = 0;
                foreach ($cartItems as $ci) {
                    if (
                        ($ci['movieId'] ?? '') === $movieId &&
                        ($ci['date'] ?? '') === $date &&
                        (date('H:i', strtotime($ci['time'])) === date('H:i', strtotime($validated['time']))) &&
                        ($ci['cinema'] ?? '') === $cinema
                    ) {
                        $cartQty += (int) ($ci['quantity'] ?? 0);
                    }
                }

                $projected = $booked + $cartQty + (int) $validated['quantity'];
                if ($projected > $capacity) {
                    $remaining = max(0, $capacity - $booked - $cartQty);
                    throw new \Exception("Capacity reached for this showtime. Remaining seats: {$remaining}.");
                }
            } catch (\Exception $e) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => $e->getMessage(),
                    ], 422);
                }
                return back()->withErrors(['cart' => $e->getMessage()])->withInput();
            }

            $this->cartService->addItem($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Item added to cart',
                    'items' => $this->cartService->getItems(),
                    'count' => $this->cartService->getCount(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }
            return redirect()->route('booking')->with('message', 'Added to cart');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to add item to cart',
                    'message' => $e->getMessage(),
                ], 400);
            }
            return back()->withErrors(['cart' => 'Failed to add item'])->withInput();
        }
    }

    /**
     * Remove item from cart
     */
    public function destroy($index, Request $request)
    {
        try {
            $this->cartService->removeItem((int) $index);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Item removed from cart',
                    'items' => $this->cartService->getItems(),
                    'count' => $this->cartService->getCount(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }
            return redirect()->route('booking')->with('message', 'Item removed');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to remove item from cart',
                ], 400);
            }
            return back()->withErrors(['cart' => 'Failed to remove item']);
        }
    }

    /**
     * Clear cart
     */
    public function clear(Request $request)
    {
        $this->cartService->clear();
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cart cleared',
                'items' => [],
                'count' => 0,
                'total' => 0,
            ]);
        }
        return redirect()->route('booking')->with('message', 'Cart cleared');
    }
}
