<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{
    /**
     * Get all movies with showtimes and viewers count
     */
    public function index()
    {
        try {
            // Count viewers (tickets sold) excluding cancelled bookings, and only for past dates (watched)
            $movies = Movie::with('showtimes')
                ->withCount([
                    'bookingItems as viewers_total' => function ($q) {
                        $q->whereDate('booking_date', '<=', today())
                          ->whereHas('booking', function ($b) {
                              $b->where('status', '!=', 'Cancelled');
                          });
                    }
                ])
                ->orderBy('title')
                ->get();

            $moviesWithShowtimes = $movies->map(function ($movie) {
                return [
                    'id' => $movie->id,
                    'title' => $movie->title,
                    'price' => (float) $movie->price,
                    'poster' => $movie->poster,
                    'audience' => $movie->audience,
                    'viewers_total' => (int) ($movie->viewers_total ?? 0),
                    'showtimes' => $movie->showtimes->map(function ($showtime) {
                        return date('H:i', strtotime($showtime->showtime));
                    })->toArray(),
                    'created_at' => $movie->created_at,
                    'updated_at' => $movie->updated_at,
                ];
            });

            return response()->json($moviesWithShowtimes);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch movies',
            ], 500);
        }
    }

    /**
     * Get single movie
     */
    public function show($id)
    {
        try {
            $movie = Movie::with('showtimes')
                ->withCount([
                    'bookingItems as viewers_total' => function ($q) {
                        $q->whereDate('booking_date', '<=', today())
                          ->whereHas('booking', function ($b) {
                              $b->where('status', '!=', 'Cancelled');
                          });
                    }
                ])->find($id);

            if (!$movie) {
                return response()->json([
                    'error' => 'Movie not found',
                ], 404);
            }

            return response()->json([
                'id' => $movie->id,
                'title' => $movie->title,
                'price' => (float) $movie->price,
                'poster' => $movie->poster,
                'audience' => $movie->audience,
                'viewers_total' => (int) ($movie->viewers_total ?? 0),
                'showtimes' => $movie->showtimes->map(function ($showtime) {
                    return date('H:i', strtotime($showtime->showtime));
                })->toArray(),
                'created_at' => $movie->created_at,
                'updated_at' => $movie->updated_at,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch movie',
            ], 500);
        }
    }

    /**
     * Create new movie (admin only)
     */
    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'Admin access required',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'title' => 'required|string',
                'price' => 'required|numeric|min:0',
                'poster' => 'nullable|string',
                'audience' => 'required|in:G,PG,R-13,R-16,R-18',
                'showtimes' => 'required|array|min:1',
                'showtimes.*' => 'required|string',
            ]);

            $movie = Movie::create([
                'title' => trim($validated['title']),
                'price' => $validated['price'],
                'poster' => $validated['poster'] ?? null,
                'audience' => $validated['audience'],
            ]);

            // Insert showtimes
            foreach ($validated['showtimes'] as $time) {
                Showtime::create([
                    'movie_id' => $movie->id,
                    'showtime' => $time,
                ]);
            }

            return response()->json([
                'success' => true,
                'id' => $movie->id,
                'message' => 'Movie created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create movie',
            ], 500);
        }
    }

    /**
     * Update movie (admin only)
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'Admin access required',
            ], 403);
        }

        try {
            $movie = Movie::find($id);

            if (!$movie) {
                return response()->json([
                    'error' => 'Movie not found',
                ], 404);
            }

            $validated = $request->validate([
                'title' => 'required|string',
                'price' => 'required|numeric|min:0',
                'poster' => 'nullable|string',
                'audience' => 'required|in:G,PG,R-13,R-16,R-18',
                'showtimes' => 'sometimes|array',
                'showtimes.*' => 'string',
            ]);

            $movie->update([
                'title' => trim($validated['title']),
                'price' => $validated['price'],
                'poster' => $validated['poster'] ?? null,
                'audience' => $validated['audience'],
            ]);

            // Update showtimes if provided
            if (isset($validated['showtimes'])) {
                // Delete old showtimes
                Showtime::where('movie_id', $id)->delete();

                // Insert new showtimes
                foreach ($validated['showtimes'] as $time) {
                    Showtime::create([
                        'movie_id' => $movie->id,
                        'showtime' => $time,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Movie updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update movie',
            ], 500);
        }
    }

    /**
     * Delete movie (admin only)
     */
    public function destroy($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'Admin access required',
            ], 403);
        }

        try {
            $movie = Movie::find($id);

            if (!$movie) {
                return response()->json([
                    'error' => 'Movie not found',
                ], 404);
            }

            $movie->delete();

            return response()->json([
                'success' => true,
                'message' => 'Movie deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete movie',
            ], 500);
        }
    }
}
