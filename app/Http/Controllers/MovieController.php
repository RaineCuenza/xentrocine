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
                ->where('is_active', true)
                // Respect availability window (default 3 weeks, max 1 month)
                ->whereDate('available_until', '>=', today())
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
                    'is_active' => (bool) $movie->is_active,
                    'showtimes' => $movie->showtimes->map(function ($showtime) {
                        return $showtime->formatted_range;
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

            if (!$movie || !$movie->is_active) {
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
                'is_active' => (bool) $movie->is_active,
                'showtimes' => $movie->showtimes->map(function ($showtime) {
                    return $showtime->formatted_range;
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
     * Get today's valid showtimes for a movie (source of truth: showtimes table)
     * - Respects movie listing window
     * - Filters out showtimes that already ended
     * - Tags items as now/next/later in label
     */
    public function todayShowtimes($id)
    {
        try {
            $movie = Movie::with('showtimes')->find($id);
            if (!$movie || !$movie->is_active) {
                return response()->json(['error' => 'Movie not found'], 404);
            }

            $today = today();
            // Must be within listing window
            $inWindow = (!$movie->listing_start || $today->gte($movie->listing_start)) && (!$movie->listing_end || $today->lte($movie->listing_end));
            if (!$inWindow) {
                return response()->json(['showtimes' => []]);
            }

            $durationMin = (int) ($movie->duration ?? 90);
            $now = now();

            // Map showtimes to labels and filter out those already ended
            $items = collect($movie->showtimes)
                ->map(function ($st) use ($durationMin, $today) {
                    $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $today->toDateString() . ' ' . date('H:i', strtotime($st->showtime)));
                    $end = $start->copy()->addMinutes($durationMin);
                    return [
                        'id' => $st->id,
                        'time' => $start->format('H:i'),
                        'start' => $start,
                        'end' => $end,
                    ];
                })
                ->filter(function ($row) use ($now) {
                    // keep those not ended yet
                    return $row['end']->gt($now);
                })
                ->sortBy('time')
                ->values()
                ->map(function ($row) use ($now) {
                    $label = $row['time'] . ' ';
                    if ($row['start']->lte($now) && $row['end']->gt($now)) {
                        $label .= '• Now Showing';
                    } elseif ($row['start']->lte($now->copy()->addHour())) {
                        $label .= '• Next';
                    } else {
                        $label .= '• Later Today';
                    }
                    return [
                        'id' => $row['id'],
                        'label' => $label,
                        'time' => $row['time'],
                    ];
                });

            return response()->json(['showtimes' => $items]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to fetch showtimes'], 500);
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
                'duration' => 'sometimes|integer|min:30|max:300',
                'showtimes' => 'required|array|min:1',
                'showtimes.*' => 'required|string',
                // Optional availability end date; capped at +1 month
                'available_until' => 'nullable|date',
                // New listing window fields
                'listing_start' => 'required|date|after_or_equal:today',
                'listing_end' => 'required|date|after_or_equal:listing_start',
            ]);

            // Compute availability: default 3 weeks, cap 1 month if custom provided
            $defaultAvailableUntil = now()->addWeeks(3);
            $maxAvailableUntil = now()->addMonth();
            $requestedAvailable = isset($validated['available_until']) ? \Carbon\Carbon::parse($validated['available_until']) : null;
            $finalAvailableUntil = $requestedAvailable ? ($requestedAvailable->greaterThan($maxAvailableUntil) ? $maxAvailableUntil : $requestedAvailable) : ($defaultAvailableUntil->greaterThan($maxAvailableUntil) ? $maxAvailableUntil : $defaultAvailableUntil);

            // Enforce max 30 days for listing window
            $start = \Carbon\Carbon::parse($validated['listing_start']);
            $end = \Carbon\Carbon::parse($validated['listing_end']);
            if ($end->gt($start->copy()->addDays(30))) {
                return response()->json([
                    'error' => 'A movie can only run for a maximum of 1 month from its start date.',
                ], 422);
            }

            $movie = Movie::create([
                'title' => trim($validated['title']),
                'price' => $validated['price'],
                'poster' => $validated['poster'] ?? null,
                'audience' => $validated['audience'],
                'duration' => $validated['duration'] ?? 90,
                'is_active' => true,
                'available_until' => $finalAvailableUntil,
                'listing_start' => $start->toDateString(),
                'listing_end' => $end->toDateString(),
            ]);

            // Insert showtimes with computed end_time
            foreach ($validated['showtimes'] as $time) {
                $start = \Carbon\Carbon::createFromFormat('H:i', date('H:i', strtotime($time)));
                $end = $start->copy()->addMinutes($movie->duration);
                Showtime::create([
                    'movie_id' => $movie->id,
                    'showtime' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                ]);
            }

            return response()->json([
                'success' => true,
                'id' => $movie->id,
                'is_active' => true,
                'available_until' => $movie->available_until ? (string) $movie->available_until : null,
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
                'duration' => 'sometimes|integer|min:30|max:300',
                'showtimes' => 'sometimes|array',
                'showtimes.*' => 'string',
                'is_active' => 'sometimes|boolean',
                'available_until' => 'sometimes|date',
                'listing_start' => 'sometimes|date',
                'listing_end' => 'sometimes|date|after_or_equal:listing_start',
            ]);

            $updateData = [
                'title' => trim($validated['title']),
                'price' => $validated['price'],
                'poster' => $validated['poster'] ?? null,
                'audience' => $validated['audience'],
            ];

            if (array_key_exists('is_active', $validated)) {
                $updateData['is_active'] = (bool) $validated['is_active'];
            }

            if (array_key_exists('available_until', $validated)) {
                $maxAvailableUntil = now()->addMonth();
                $requestedAvailable = \Carbon\Carbon::parse($validated['available_until']);
                $updateData['available_until'] = $requestedAvailable->greaterThan($maxAvailableUntil) ? $maxAvailableUntil : $requestedAvailable;
            }

            // Update listing window with 30-day cap
            if (array_key_exists('listing_start', $validated) || array_key_exists('listing_end', $validated)) {
                $start = isset($validated['listing_start']) ? \Carbon\Carbon::parse($validated['listing_start']) : ($movie->listing_start ? \Carbon\Carbon::parse($movie->listing_start) : now());
                $end = isset($validated['listing_end']) ? \Carbon\Carbon::parse($validated['listing_end']) : ($movie->listing_end ? \Carbon\Carbon::parse($movie->listing_end) : $start->copy()->addDays(14));
                if ($end->lt($start)) {
                    return response()->json([
                        'error' => 'Listing end cannot be before listing start.',
                    ], 422);
                }
                if ($end->gt($start->copy()->addDays(30))) {
                    return response()->json([
                        'error' => 'A movie can only run for a maximum of 1 month from its start date.',
                    ], 422);
                }
                $updateData['listing_start'] = $start->toDateString();
                $updateData['listing_end'] = $end->toDateString();
            }

            if (array_key_exists('duration', $validated)) {
                $updateData['duration'] = (int) $validated['duration'];
            }

            $movie->update($updateData);

            // Update showtimes if provided
            if (isset($validated['showtimes'])) {
                // Delete old showtimes
                Showtime::where('movie_id', $id)->delete();

                // Insert new showtimes with computed end_time
                foreach ($validated['showtimes'] as $time) {
                    $start = \Carbon\Carbon::createFromFormat('H:i', date('H:i', strtotime($time)));
                    $end = $start->copy()->addMinutes($movie->duration);
                    Showtime::create([
                        'movie_id' => $movie->id,
                        'showtime' => $start->format('H:i'),
                        'end_time' => $end->format('H:i'),
                    ]);
                }
            }

            $movie->load(['showtimes' => function ($query) {
                $query->orderBy('showtime');
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Movie updated successfully',
                'movie' => [
                    'id' => $movie->id,
                    'title' => $movie->title,
                    'price' => (float) $movie->price,
                    'poster' => $movie->poster,
                    'audience' => $movie->audience,
                    'is_active' => (bool) $movie->is_active,
                    'available_until' => $movie->available_until ? (string) $movie->available_until : null,
                    'showtimes' => $movie->showtimes->map(function ($showtime) {
                        return $showtime->formatted_range;
                    })->values()->toArray(),
                ],
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

            if (!$movie->is_active) {
                return response()->json([
                    'success' => true,
                    'message' => 'Movie already inactive',
                ]);
            }

            $movie->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Movie marked as inactive',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete movie',
            ], 500);
        }
    }

    /**
     * List movies currently showing in real-time (based on start time and assumed 2h duration)
     */
    public function showingNow()
    {
        try {
            $now = now();
            $startWindow = $now->copy()->subHours(2); // assume 2-hour duration

            $movies = Movie::with(['showtimes'])
                ->where('is_active', true)
                ->whereDate('available_until', '>=', today())
                ->get();

            $showing = [];
            foreach ($movies as $movie) {
                foreach ($movie->showtimes as $st) {
                    // Build today's datetime for the showtime
                    $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i', today()->toDateString() . ' ' . date('H:i', strtotime($st->showtime)));
                    if ($start->between($startWindow, $now)) {
                        $showing[] = [
                            'movie_id' => $movie->id,
                            'title' => $movie->title,
                            'showtime' => $start->format('H:i'),
                            'started_at' => $start->toIso8601String(),
                        ];
                    }
                }
            }

            // Sort by showtime descending (most recent first)
            usort($showing, function ($a, $b) {
                return strcmp($b['showtime'], $a['showtime']);
            });

            return response()->json($showing);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch currently showing movies'], 500);
        }
    }
}
