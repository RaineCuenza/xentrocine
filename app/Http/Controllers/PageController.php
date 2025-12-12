<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        // Cart service remains injected for backward compatibility, but
        // the client booking page no longer uses a cart concept.
        $this->cartService = $cartService;
    }

    /**
     * Home page - Display movies
     */
    public function index()
    {
        // If cashier/staff is logged in, show cashier dashboard instead of public home
        if (Auth::check()) {
            $role = Auth::user()->role;
            if (in_array($role, ['staff', 'admin'])) {
                $now = now();
                $today = today();

                // Default cinema capacity map
                $cinemaConfig = (array) config('app.cinemas', [
                    'Cinema 1' => 120,
                    'Cinema 2' => 120,
                ]);
                $cinemas = array_keys($cinemaConfig);

                // Fetch active movies with today's valid listing window
                $movies = Movie::with(['showtimes' => function ($q) {
                        $q->orderBy('showtime');
                    }])
                    ->where('is_active', true)
                    ->whereDate('listing_start', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('listing_end')
                          ->orWhereDate('listing_end', '>=', $today);
                    })
                    ->orderBy('title')
                    ->get();

                $nowShowing = [];
                $upcoming = [];

                foreach ($movies as $movie) {
                    foreach ($movie->showtimes as $st) {
                        $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $today->toDateString() . ' ' . date('H:i', strtotime($st->showtime)));
                        $end = $start->copy()->addHours(2);

                        foreach ($cinemas as $cinema) {
                            $booked = BookingItem::where('movie_id', $movie->id)
                                ->whereDate('booking_date', $today)
                                ->where('showtime', date('H:i:s', strtotime($st->showtime)))
                                ->where('cinema', $cinema)
                                ->whereHas('booking', function ($b) {
                                    $b->where('status', '!=', 'Cancelled');
                                })
                                ->sum('quantity');

                            $row = [
                                'movie_id' => $movie->id,
                                'title' => $movie->title,
                                'start_time' => $start->format('H:i'),
                                'end_time' => $end->format('H:i'),
                                'cinema' => $cinema,
                                'booked' => (int) $booked,
                                'capacity' => (int) ($cinemaConfig[$cinema] ?? 120),
                            ];

                            if ($start->lte($now) && $end->gt($now)) {
                                $nowShowing[] = $row;
                            } elseif ($start->gt($now)) {
                                $upcoming[] = $row;
                            }
                        }
                    }
                }

                usort($nowShowing, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
                usort($upcoming, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));

                return view('cashier-home', [
                    'now' => $now,
                    'nowShowing' => $nowShowing,
                    'upcomingShowtimes' => $upcoming,
                ]);
            }
        }

        $movies = Movie::with('showtimes')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('index', compact('movies'));
    }

    /**
     * Booking page - Display movies and user bookings (no cart)
     */
    public function booking(Request $request)
    {
        $movies = Movie::with('showtimes')
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        // Selected movie for non-JS showtime population
        $selectedMovieId = $request->query('movie');
        $selectedMovie = null;
        if ($selectedMovieId) {
            $selectedMovie = $movies->firstWhere('id', $selectedMovieId);
            if ($selectedMovie) {
                // Ensure Carbon instances for listing dates
                $selectedMovie->listing_start = $selectedMovie->listing_start ? \Carbon\Carbon::parse($selectedMovie->listing_start) : \Carbon\Carbon::today();
                $selectedMovie->listing_end = $selectedMovie->listing_end ? \Carbon\Carbon::parse($selectedMovie->listing_end) : null;
            }
        }
        // Do NOT auto-select a movie; show placeholders by default

        // Get user's bookings if authenticated
        $bookings = collect();
        if (Auth::check()) {
            $bookings = Booking::with(['items'])
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return view('booking', compact('movies', 'bookings', 'selectedMovie'));
    }

    /**
     * Admin page - Display movies and bookings
     */
    public function admin(Request $request)
    {
        // Check if user is admin
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Unauthorized access');
        }

        // --- Movies filters & pagination (separate Active vs Archived) ---
        // Active movies filters
        $mSearchActive = trim((string) $request->query('m_q_a', ''));
        $mAudienceActive = $request->query('m_audience_a', 'all');
        $moviesActiveQuery = Movie::with('showtimes')->withCount('bookingItems')->where('is_active', true);
        if ($mSearchActive !== '') {
            $moviesActiveQuery->where('title', 'like', '%' . str_replace('%','\%',$mSearchActive) . '%');
        }
        if ($mAudienceActive !== 'all') {
            $moviesActiveQuery->where('audience', $mAudienceActive);
        }
        $moviesActive = $moviesActiveQuery->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Archived movies filters
        $mSearchArchived = trim((string) $request->query('m_q_r', ''));
        $mAudienceArchived = $request->query('m_audience_r', 'all');
        $moviesArchivedQuery = Movie::with('showtimes')->withCount('bookingItems')->where('is_active', false);
        if ($mSearchArchived !== '') {
            $moviesArchivedQuery->where('title', 'like', '%' . str_replace('%','\%',$mSearchArchived) . '%');
        }
        if ($mAudienceArchived !== 'all') {
            $moviesArchivedQuery->where('audience', $mAudienceArchived);
        }
        $moviesArchived = $moviesArchivedQuery->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // --- Bookings filters & pagination ---
    $bQuery = Booking::with(['items.movie', 'user']);
        $bSearchTitle = trim((string) $request->query('b_q', ''));
        $bStatus = $request->query('b_status', 'all'); // all|Paid|Pending|Cancelled
        $bFrom = $request->query('b_from');
        $bTo = $request->query('b_to');

        if ($bSearchTitle !== '') {
            $bQuery->whereHas('items', function ($q) use ($bSearchTitle) {
                $q->where('movie_title', 'like', '%' . str_replace('%','\%',$bSearchTitle) . '%');
            });
        }
        if ($bStatus !== 'all') {
            $bQuery->where('status', $bStatus);
        }
        if ($bFrom) {
            $bQuery->whereHas('items', function ($q) use ($bFrom) {
                $q->whereDate('booking_date', '>=', $bFrom);
            });
        }
        if ($bTo) {
            $bQuery->whereHas('items', function ($q) use ($bTo) {
                $q->whereDate('booking_date', '<=', $bTo);
            });
        }

        $bookingsPaginated = $bQuery->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        // Calculate KPIs
        $kpis = [
            'movies' => Movie::where('is_active', true)->count(),
            'movies_archived' => Movie::where('is_active', false)->count(),
            'bookings' => Booking::count(),
            'paid' => Booking::where('status', 'Paid')->count(),
            'pending' => Booking::where('status', 'Pending')->count(),
            'cancelled' => Booking::where('status', 'Cancelled')->count(),
        ];

        return view('admin', [
            'moviesActive' => $moviesActive,
            'moviesArchived' => $moviesArchived,
            'bookings' => $bookingsPaginated,
            'kpis' => $kpis,
            'filters' => [
                'm_q_a' => $mSearchActive,
                'm_audience_a' => $mAudienceActive,
                'm_q_r' => $mSearchArchived,
                'm_audience_r' => $mAudienceArchived,
                'b_q' => $bSearchTitle,
                'b_status' => $bStatus,
                'b_from' => $bFrom,
                'b_to' => $bTo,
            ],
        ]);
    }

    /**
     * Staff page
     */
    public function staff()
    {
        // Check if user is staff or admin
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'staff'])) {
            return redirect()->route('home')->with('error', 'Unauthorized access');
        }

        // Only show movies that:
        // 1. Are active
        // 2. Have showtimes
        // 3. Are currently within their listing window (today is between listing_start and listing_end)
        $movies = Movie::with('showtimes')
            ->where('is_active', true)
            ->has('showtimes') // Only movies with showtimes
            ->whereDate('listing_start', '<=', today())
            ->whereDate('listing_end', '>=', today())
            ->orderBy('title')
            ->get();

        return view('staff', compact('movies'));
    }

    /**
     * About page
     */
    public function about()
    {
        return view('about');
    }

    /**
     * Login page
     */
    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('login');
    }

    /**
     * Signup page
     */
    public function signup()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('signup');
    }

    /**
     * QR Test page
     */
    public function qrTest()
    {
        $bookings = [];
        
        if (Auth::check()) {
            $bookings = Booking::where('user_id', Auth::id())
                ->orWhere(function($query) {
                    // Admin can see all bookings
                    if (Auth::user()->role === 'admin') {
                        $query->whereNotNull('id');
                    }
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('qr-test', compact('bookings'));
    }

    /**
     * My Bookings page - View user's booking history
     */
    public function myBookings()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view your bookings');
        }

        $bookings = Booking::with(['items', 'checkedInBy'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('my-bookings', compact('bookings'));
    }
}
