<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Cinema;
use App\Models\Showtime;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
                $expectedRevenueTotal = 0;
                $upcomingReservedTotal = 0;
                $activeBooked = 0;
                $activeCapacity = 0;
                $nextScreeningTime = null;

                foreach ($movies as $movie) {
                    foreach ($movie->showtimes as $st) {
                        $durationMin = (int) ($movie->duration ?? 120);
                        $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $today->toDateString() . ' ' . date('H:i', strtotime($st->showtime)));
                        $end = $start->copy()->addMinutes($durationMin);
                        $price = (float) ($movie->price ?? 0);

                        // Pick the cinema with the most bookings (or default to the first configured cinema)
                        $best = [
                            'cinema' => $cinemas[0] ?? 'Cinema 1',
                            'booked' => 0,
                            'capacity' => (int) ($cinemaConfig[$cinemas[0] ?? 'Cinema 1'] ?? 120),
                        ];
                        foreach ($cinemas as $cinema) {
                            $booked = BookingItem::where('movie_id', $movie->id)
                                ->whereDate('booking_date', $today)
                                ->where('showtime', date('H:i:s', strtotime($st->showtime)))
                                ->where('cinema', $cinema)
                                ->whereHas('booking', function ($b) {
                                    $b->where('status', '!=', 'Cancelled');
                                })
                                ->sum('quantity');
                            if ($booked >= $best['booked']) {
                                $best['cinema'] = $cinema;
                                $best['booked'] = (int) $booked;
                                $best['capacity'] = (int) ($cinemaConfig[$cinema] ?? $best['capacity']);
                            }
                        }

                        $expectedRevenue = $best['booked'] * $price;
                        $row = [
                            'movie_id' => $movie->id,
                            'title' => $movie->title,
                            'start_time' => $start->format('H:i'),
                            'end_time' => $end->format('H:i'),
                            'cinema' => $best['cinema'],
                            'booked' => $best['booked'],
                            'capacity' => $best['capacity'],
                            'expected_revenue' => $expectedRevenue,
                            'price' => $price,
                            'duration' => $durationMin,
                            'progress' => $best['capacity'] > 0 ? round(($best['booked'] / $best['capacity']) * 100) : 0,
                            'start' => $start,
                        ];

                        if ($start->lte($now) && $end->gt($now)) {
                            $nowShowing[] = $row;
                            $activeBooked += $best['booked'];
                            $activeCapacity += $best['capacity'];
                        } elseif ($start->gt($now)) {
                            $upcoming[] = $row;
                            $expectedRevenueTotal += $expectedRevenue;
                            $upcomingReservedTotal += $best['booked'];
                            if ($nextScreeningTime === null || $start->lt($nextScreeningTime)) {
                                $nextScreeningTime = $start;
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
                    'expectedRevenueTotal' => $expectedRevenueTotal,
                    'upcomingReservedTotal' => $upcomingReservedTotal,
                    'upcomingShowsCount' => count($upcoming),
                    'activeOccupancy' => $activeCapacity > 0 ? round(($activeBooked / $activeCapacity) * 100) : 0,
                    'nextScreeningTime' => $nextScreeningTime,
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

        // Cinema availability/status for dropdown
        $cinemas = Cinema::orderBy('name')->get();

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
        return view('booking', compact('movies', 'bookings', 'selectedMovie', 'cinemas'));
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

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $now = Carbon::now();
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $prevStart = $periodStart->copy()->subMonth();
        $prevEnd = $periodStart->copy()->subSecond();
        $activeStatuses = ['Paid', 'Pending'];

        $baseItemQuery = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', $activeStatuses);

        $monthlyRevenue = (clone $baseItemQuery)
            ->whereBetween('booking_items.booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('COALESCE(SUM(booking_items.price * booking_items.quantity),0) as total')
            ->value('total') ?? 0;

        $prevRevenue = (clone $baseItemQuery)
            ->whereBetween('booking_items.booking_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->selectRaw('COALESCE(SUM(booking_items.price * booking_items.quantity),0) as total')
            ->value('total') ?? 0;

        $monthlyTickets = (clone $baseItemQuery)
            ->whereBetween('booking_items.booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('booking_items.quantity');

        $monthlyBookings = Booking::whereIn('status', $activeStatuses)
            ->whereHas('items', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            })
            ->count();

        $monthlyCustomers = Booking::whereIn('status', $activeStatuses)
            ->whereHas('items', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            })
            ->distinct('email')
            ->count('email');

        $growthPct = $prevRevenue > 0 ? (($monthlyRevenue - $prevRevenue) / $prevRevenue) * 100 : null;
        $avgTicket = $monthlyTickets > 0 ? $monthlyRevenue / $monthlyTickets : 0;

        // Daily trend (last 7 days)
        $trendStart = $now->copy()->subDays(6)->startOfDay();
        $dailyRows = (clone $baseItemQuery)
            ->whereBetween('booking_items.booking_date', [$trendStart->toDateString(), $now->toDateString()])
            ->selectRaw('booking_items.booking_date as day, COALESCE(SUM(booking_items.price * booking_items.quantity),0) as revenue, SUM(booking_items.quantity) as tickets, COUNT(DISTINCT booking_items.booking_id) as bookings')
            ->groupBy('booking_items.booking_date')
            ->orderBy('booking_items.booking_date')
            ->get()
            ->keyBy('day');
        $dailyTrend = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $trendStart->copy()->addDays($i)->toDateString();
            $row = $dailyRows[$d] ?? null;
            $dailyTrend[] = [
                'label' => Carbon::parse($d)->format('D'),
                'revenue' => $row->revenue ?? 0,
                'bookings' => $row->bookings ?? 0,
            ];
        }

        // Top movies by revenue (current month)
        $topMovies = (clone $baseItemQuery)
            ->whereBetween('booking_items.booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('booking_items.movie_title as title, SUM(booking_items.price * booking_items.quantity) as revenue, SUM(booking_items.quantity) as tickets')
            ->groupBy('booking_items.movie_title')
            ->orderByDesc('revenue')
            ->limit(4)
            ->get();

        // Cinema performance (current month)
        $cinemas = Cinema::orderBy('name')->get();
        $cinemaPerformance = [];
        foreach ($cinemas as $cinema) {
            $capacity = (int) ($cinema->capacity ?? 120);
            $row = (clone $baseItemQuery)
                ->where('booking_items.cinema', $cinema->name)
                ->whereBetween('booking_items.booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->selectRaw('SUM(booking_items.price * booking_items.quantity) as revenue, SUM(booking_items.quantity) as tickets, COUNT(DISTINCT CONCAT(booking_items.booking_date, " ", booking_items.showtime)) as screenings')
                ->first();
            $tickets = (int) ($row->tickets ?? 0);
            $screenings = max(1, (int) ($row->screenings ?? 0));
            $occupancy = $capacity > 0 ? round(($tickets / ($capacity * $screenings)) * 100) : 0;
            $cinemaPerformance[] = [
                'name' => $cinema->name,
                'capacity' => $capacity,
                'revenue' => (float) ($row->revenue ?? 0),
                'tickets' => $tickets,
                'screenings' => $screenings,
                'occupancy' => max(0, min(100, $occupancy)),
            ];
        }

        // Peak hours (last 30 days)
        $slotDefs = [
            ['label' => '12:00-15:00', 'start' => '12:00:00', 'end' => '15:00:00'],
            ['label' => '15:00-18:00', 'start' => '15:00:00', 'end' => '18:00:00'],
            ['label' => '18:00-21:00', 'start' => '18:00:00', 'end' => '21:00:00'],
            ['label' => '21:00-01:00', 'start' => '21:00:00', 'end' => '01:00:00'],
        ];
        $slotData = [];
        $recentItems = (clone $baseItemQuery)
            ->whereBetween('booking_items.booking_date', [$now->copy()->subDays(30)->toDateString(), $now->toDateString()])
            ->get(['booking_items.showtime', 'booking_items.price', 'booking_items.quantity']);
        foreach ($slotDefs as $slot) {
            $slotData[$slot['label']] = ['bookings' => 0, 'revenue' => 0];
        }
        foreach ($recentItems as $item) {
            $time = Carbon::parse($item->showtime)->format('H:i:s');
            foreach ($slotDefs as $slot) {
                $inSlot = false;
                if ($slot['start'] < $slot['end']) {
                    $inSlot = $time >= $slot['start'] && $time < $slot['end'];
                } else {
                    $inSlot = $time >= $slot['start'] || $time < $slot['end'];
                }
                if ($inSlot) {
                    $slotData[$slot['label']]['bookings'] += 1;
                    $slotData[$slot['label']]['revenue'] += (float) ($item->price * $item->quantity);
                    break;
                }
            }
        }

        // Payment split (current month)
        $paymentRows = Booking::whereIn('status', $activeStatuses)
            ->whereHas('items', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            })
            ->selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');
        $paymentTotals = [
            'cash' => (int) ($paymentRows['cash'] ?? 0),
            'gcash' => (int) ($paymentRows['gcash'] ?? $paymentRows['digital'] ?? 0),
        ];
        $paymentTotalCount = array_sum($paymentTotals);
        $paymentSplit = [
            'cash' => $paymentTotalCount ? round(($paymentTotals['cash'] / $paymentTotalCount) * 100) : 0,
            'gcash' => $paymentTotalCount ? round(($paymentTotals['gcash'] / $paymentTotalCount) * 100) : 0,
        ];

        // Customer type breakdown (placeholder until explicit flags are stored)
        $customerTypes = [
            'regular' => 100,
            'senior' => 0,
        ];

        $analytics = [
            'period' => [
                'month' => $periodStart->format('F'),
                'year' => $periodStart->year,
            ],
            'month' => [
                'revenue' => $monthlyRevenue,
                'prevRevenue' => $prevRevenue,
                'growthPct' => $growthPct,
                'customers' => $monthlyCustomers,
                'bookings' => $monthlyBookings,
                'tickets' => $monthlyTickets,
                'avgTicket' => $avgTicket,
            ],
            'dailyTrend' => $dailyTrend,
            'topMovies' => $topMovies,
            'cinemas' => $cinemaPerformance,
            'peakHours' => $slotData,
            'paymentSplit' => $paymentSplit,
            'customerTypes' => $customerTypes,
        ];

        return view('admin', [
            'analytics' => $analytics,
        ]);
    }

    public function adminManage(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Unauthorized access');
        }

        // --- Movies filters & pagination (separate Active vs Archived) ---
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

        $kpis = [
            'movies' => Movie::where('is_active', true)->count(),
            'movies_archived' => Movie::where('is_active', false)->count(),
            'bookings' => Booking::count(),
            'paid' => Booking::where('status', 'Paid')->count(),
            'pending' => Booking::where('status', 'Pending')->count(),
            'cancelled' => Booking::where('status', 'Cancelled')->count(),
        ];

        $cinemas = Cinema::orderBy('name')->get();

        return view('admin-manage', [
            'moviesActive' => $moviesActive,
            'moviesArchived' => $moviesArchived,
            'bookings' => $bookingsPaginated,
            'cinemas' => $cinemas,
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

        $cinemas = Cinema::orderBy('name')->get();

        return view('staff', compact('movies', 'cinemas'));
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
     * Forgot password page
     */
    public function forgotPassword()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('forgot-password');
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
}
