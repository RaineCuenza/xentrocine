<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Cinema;
use App\Mail\BookingConfirmation;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            
            $bookingsQuery = Booking::with('items');

            if ((($user->role ?? 'client') !== 'admin')) {
                // Users see only their bookings
                $bookingsQuery->where('user_id', $user->id ?? '');
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

        // Block admin users from making bookings
        if ((Auth::user()->role ?? 'client') === 'admin') {
            abort(403, 'Admins are not allowed to make bookings.');
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
            // Build normalized cart items and payment method across all flows
            [$cartItems, $paymentMethod, $isSingleWebForm, $usingSessionCart, $singleValidated] = $this->buildCartItemsAndPayment($request);

            /** @var \App\Models\User|null $user */
            $user = Auth::user();

                // Reference number is optional for all flows; no enforcement here

            $customerName = trim($singleValidated['customer_name'] ?? $request->input('customer_name', $user->name));
            if ($customerName === '') {
                $customerName = $user->name;
            }

            DB::beginTransaction();

            // Prevent duplicate booking by the same user for the same session (movie/date/time/cinema)
            if ($isSingleWebForm && $singleValidated) {
                $dupeExists = $this->duplicateExistsForSingle($singleValidated, $user);
                if ($dupeExists) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'Duplicate booking detected for this session.',
                        ], 409);
                    }
                    return back()->withErrors(['booking' => 'This customer already has a booking for the selected movie/session.'])->withInput();
                }
            }

            // Build DB-ready booking items and compute total (enforces listing dates and capacity)
            [$bookingItems, $total] = $this->computeBookingItemsAndTotal($cartItems);

            // Optional receipt upload (web form only)
            $receiptPath = $this->storeReceipt($request, $paymentMethod);

            // Determine initial status
            $initialStatus = $this->determineInitialStatus($user, $paymentMethod);

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'name' => $customerName,
                'email' => $user->email,
                'phone' => '',
                    // Reference number is optional; do not require nor infer it
                    'reference' => $request->input('reference', ''),
                'total' => $total,
                'status' => $initialStatus,
                'payment_method' => $paymentMethod,
                'et_qr_token' => 'et_' . Str::random(40),
                'gcash_receipt_path' => $receiptPath,
            ]);

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

            if ($usingSessionCart) {
                $this->cartService->clear();
            }

            // Dispatch email (non-fatal on failure)
            $this->sendBookingEmail($booking);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'bookingId' => $booking->id,
                    'message' => 'Booking confirmed successfully',
                ]);
            }
            return back()->with('message', 'Walk-in booking confirmed successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            $errors = $e->errors();
            if (isset($errors['items.0.date'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => $errors['items.0.date'][0] ?? 'Invalid show date selected.',
                    ], 422);
                }
                return back()->withErrors(['booking' => $errors['items.0.date'][0] ?? 'Invalid show date selected.'])->withInput();
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
     * Build normalized cart items and payment method for all input modes.
     * Returns: [cartItems, paymentMethod, isSingleWebForm, usingSessionCart, singleValidated]
     */
    private function buildCartItemsAndPayment(Request $request): array
    {
        $cartItems = [];
        $usingSessionCart = false;
        $isSingleWebForm = $request->has('movieId') && $request->has('showtime_id');
        $singleValidated = null;
        $paymentMethod = 'digital';

        if ($isSingleWebForm) {
            $validated = $request->validate([
                'movieId' => 'required|string',
                'show_date' => 'required|date',
                'showtime_id' => 'required|string',
                'cinema' => 'required|string',
                'quantity' => 'required|integer|min:0',
                'seniorQuantity' => 'required|integer|min:0',
                'payment_method' => 'required|in:cash,gcash',
                'customer_name' => 'nullable|string|max:120',
                'gcash_receipt' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                // gcash_reference removed; receipt image is sufficient
            ]);

            $movie = Movie::find($validated['movieId']);
            if (!$movie || !$movie->is_active) {
                abort(302, '', [
                    'Location' => url()->previous(),
                ]); // store() will return a back()->withErrors in the caller flow if needed
            }

            $showtime = Showtime::where('id', $validated['showtime_id'])
                ->where('movie_id', $movie->id)
                ->first();
            if (!$showtime) {
                abort(302, '', [
                    'Location' => url()->previous(),
                ]);
            }

            $reg = (int) $validated['quantity'];
            $sen = (int) $validated['seniorQuantity'];
            $qty = $reg + $sen;
            if ($qty < 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items.0.quantity' => 'Please select at least 1 seat.'
                ]);
            }

            $cartItems[] = [
                'movieId' => $movie->id,
                'title' => $movie->title,
                'date' => Carbon::parse($validated['show_date'])->toDateString(),
                'time' => date('H:i:s', strtotime($showtime->showtime)),
                'cinema' => $validated['cinema'],
                'quantity' => $qty,
                'seatPrice' => (float) $movie->price,
                'seniorQuantity' => $sen,
            ];

            $singleValidated = $validated;
            $paymentMethod = ($validated['payment_method'] ?? 'gcash') === 'gcash' ? 'digital' : 'cash';
        } elseif ($request->has('items')) {
            $validated = $request->validate([
                'paymentMethod' => 'nullable|in:cash,digital',
                'items' => 'required|array|min:1',
                'items.*.movieId' => 'required|string',
                'items.*.title' => 'required|string',
                'items.*.date' => 'required|date',
                'items.*.time' => 'required|string',
                'items.*.cinema' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.seatPrice' => 'required|numeric',
                'items.*.seniorQuantity' => 'nullable|integer|min:0',
            ]);
            $cartItems = $validated['items'];
            $paymentMethod = ($validated['paymentMethod'] ?? 'digital');
        } else {
            // Legacy session cart
            $usingSessionCart = true;
            $validated = $request->validate([
                'payment_method' => 'required|in:cash,gcash',
                'gcash_receipt' => 'required_if:payment_method,gcash|image|mimes:jpg,jpeg,png,webp|max:4096',
            ]);
            $cartItems = $this->cartService->getItems();
            if (count($cartItems) === 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'No items to book.'
                ]);
            }
            $paymentMethod = ($validated['payment_method'] ?? 'gcash') === 'gcash' ? 'digital' : 'cash';
        }

        return [$cartItems, $paymentMethod, $isSingleWebForm, $usingSessionCart, $singleValidated];
    }

    /**
     * Compute DB-ready items and total; enforce listing window and capacity.
     * Returns: [bookingItems, total]
     */
    private function computeBookingItemsAndTotal(array $cartItems): array
    {
        $bookingItems = [];
        $total = 0.0;
        $capacityMap = (array) config('app.cinemas', ['Cinema 1' => 120, 'Cinema 2' => 120]);

        foreach ($cartItems as $item) {
            $qty = (int) $item['quantity'];

            $movie = Movie::find($item['movieId']);
            if ($movie) {
                $showDate = Carbon::parse($item['date'])->toDateString();
                $start = $movie->listing_start ? Carbon::parse($movie->listing_start)->toDateString() : now()->toDateString();
                $endCap = $movie->listing_end ? Carbon::parse($movie->listing_end)->toDateString() : Carbon::parse($start)->addDays(30)->toDateString();
                if ($showDate < $start || $showDate > $endCap) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items.0.date' => 'Selected date is outside the allowed listing window for this movie.'
                    ]);
                }
            }

            $cinema = $item['cinema'];

            // Enforce cinema availability (DB-backed); fall back to config if cinemas table isn't used.
            $cinemaRecord = Cinema::where('name', $cinema)->first();
            if ($cinemaRecord) {
                if (($cinemaRecord->status ?? 'available') !== 'available') {
                    $label = ($cinemaRecord->status === 'maintenance') ? 'under maintenance' : (($cinemaRecord->status === 'unavailable') ? 'unavailable' : 'inactive');
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items.0.cinema' => "Selected cinema is currently {$label}. Please choose another cinema.",
                    ]);
                }
                $capacity = (int) ($cinemaRecord->capacity ?? 120);
            } else {
                $capacity = (int) ($capacityMap[$cinema] ?? 120);
            }
            $movieId = $item['movieId'];
            $date = Carbon::parse($item['date'])->toDateString();
            $timeNorm = date('H:i:s', strtotime($item['time']));

            $booked = BookingItem::where('movie_id', $movieId)
                ->whereDate('booking_date', $date)
                ->where('showtime', $timeNorm)
                ->where('cinema', $cinema)
                ->whereHas('booking', function ($b) { $b->where('status', '!=', 'Cancelled'); })
                ->lockForUpdate()
                ->sum('quantity');

            $pendingThisRequest = 0;
            foreach ($bookingItems as $bi) {
                if (
                    ($bi['movieId'] ?? '') === $movieId &&
                    (Carbon::parse($bi['date'])->toDateString() === $date) &&
                    (date('H:i:s', strtotime($bi['time'])) === $timeNorm) &&
                    (($bi['cinema'] ?? '') === $cinema)
                ) {
                    $pendingThisRequest += (int) ($bi['quantity'] ?? 0);
                }
            }

            $projected = $booked + $pendingThisRequest + $qty;
            if ($projected > $capacity) {
                $remaining = max(0, $capacity - $booked - $pendingThisRequest);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items.0.quantity' => "Capacity reached for this showtime. Remaining seats: {$remaining}."
                ]);
            }

            $bookingItems[] = [
                'movieId' => $item['movieId'],
                'title' => $item['title'],
                'date' => $item['date'],
                'time' => $timeNorm,
                'cinema' => $item['cinema'],
                'quantity' => $qty,
                'price' => $item['seatPrice'],
            ];

            // Apply senior-only discount (20%)
            $senQty = isset($item['seniorQuantity']) ? (int)$item['seniorQuantity'] : 0;
            $regQty = max(0, $qty - $senQty);
            $discountRate = 0.20;
            $regularTotal = $item['seatPrice'] * $regQty;
            $seniorTotal = $item['seatPrice'] * $senQty * (1 - $discountRate);
            $total += ($regularTotal + $seniorTotal);
        }

        return [$bookingItems, $total];
    }

    /**
     * Check for duplicate single-form booking for the same user/session.
     */
    private function duplicateExistsForSingle(array $validated, $user): bool
    {
        $show = Showtime::find($validated['showtime_id']);
        $showtimeVal = $show ? date('H:i:s', strtotime($show->showtime)) : '00:00:00';
        return BookingItem::where('movie_id', $validated['movieId'])
            ->whereDate('booking_date', Carbon::parse($validated['show_date'])->toDateString())
            ->where('showtime', $showtimeVal)
            ->where('cinema', $validated['cinema'])
            ->whereHas('booking', function ($b) use ($user) {
                $b->where('user_id', $user->id)->where('status', '!=', 'Cancelled');
            })
            ->exists();
    }

    /**
     * Store uploaded receipt if applicable.
     */
    private function storeReceipt(Request $request, string $paymentMethod): ?string
    {
        if ($paymentMethod === 'digital' && $request->hasFile('gcash_receipt')) {
            try {
                return $request->file('gcash_receipt')->store('gcash_receipts', 'public');
            } catch (\Exception $e) {
                // Re-throw as validation to keep UX consistent with original code path
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'gcash_receipt' => 'Failed to upload GCash receipt.'
                ]);
            }
        }
        return null;
    }

    /**
     * Determine initial booking status based on actor and payment method.
     */
    private function determineInitialStatus($user, string $paymentMethod): string
    {
        // Cash: Pending until accepted; Digital/GCash: Paid immediately
        if ($paymentMethod === 'digital') {
            return 'Paid';
        }
        // Staff walk-in cash bookings are also marked Paid immediately
        if ($user && (($user->role ?? 'client') === 'staff')) {
            return 'Paid';
        }
        return 'Pending';
    }

    /**
     * Send booking confirmation email; non-fatal on failure.
     */
    private function sendBookingEmail(Booking $booking): void
    {
        try {
            Mail::to($booking->email)->send(new BookingConfirmation($booking->load('items')));
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
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
        
        // Block admin users from scan actions as part of booking lifecycle
        if ($user->role === 'admin') {
            return response()->json([
                'error' => 'Admins are not allowed to make bookings.',
            ], 403);
        }

        // Only staff can scan
        if (!in_array($user->role, ['staff'])) {
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
                            'gcash_receipt_url' => ($booking->payment_method === 'digital' && $booking->gcash_receipt_path) ? asset('storage/' . $booking->gcash_receipt_path) : null,
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
                        'gcash_receipt_url' => ($booking->payment_method === 'digital' && $booking->gcash_receipt_path) ? asset('storage/' . $booking->gcash_receipt_path) : null,
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
                        'gcash_receipt_url' => ($booking->payment_method === 'digital' && $booking->gcash_receipt_path) ? asset('storage/' . $booking->gcash_receipt_path) : null,
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
