<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Booking;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Home page - Display movies
     */
    public function index()
    {
        $movies = Movie::with('showtimes')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('index', compact('movies'));
    }

    /**
     * Booking page - Display movies, cart, and user bookings
     */
    public function booking(Request $request)
    {
        $movies = Movie::with('showtimes')
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        $cart = $this->cartService->getItems();
        $cartTotal = $this->cartService->getTotal();

        // Selected movie for non-JS showtime population
        $selectedMovieId = $request->query('movie');
        $selectedMovie = null;
        if ($selectedMovieId) {
            $selectedMovie = $movies->firstWhere('id', $selectedMovieId);
        }
        // Do NOT auto-select a movie; show placeholders by default

        // Get user's bookings if authenticated
        $bookings = [];
        if (Auth::check()) {
            $bookings = Booking::with('items')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('booking', compact('movies', 'cart', 'cartTotal', 'bookings', 'selectedMovie'));
    }

    /**
     * Admin page - Display movies and bookings
     */
    public function admin()
    {
        // Check if user is admin
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Unauthorized access');
        }

        $movies = Movie::with('showtimes')
            ->withCount('bookingItems')
            ->orderByDesc('is_active')
            ->orderBy('created_at', 'desc')
            ->get();

        $bookings = Booking::with(['items', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate KPIs
        $kpis = [
            'movies' => Movie::where('is_active', true)->count(),
            'bookings' => Booking::count(),
            'paid' => Booking::where('status', 'Paid')->count(),
            'pending' => Booking::where('status', 'Pending')->count(),
            'cancelled' => Booking::where('status', 'Cancelled')->count(),
        ];

        return view('admin', compact('movies', 'bookings', 'kpis'));
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

        $movies = Movie::with('showtimes')
            ->where('is_active', true)
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
}
