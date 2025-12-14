<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Movie extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'title',
        'price',
        'poster',
        'audience',
        'duration',
        'is_active',
        'available_until',
        'listing_start',
        'listing_end',
        'total_attendance',
        'view_count',
        'popularity_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'is_active' => 'boolean',
        'available_until' => 'date',
        'listing_start' => 'date',
        'listing_end' => 'date',
        'total_attendance' => 'integer',
        'view_count' => 'integer',
        'popularity_score' => 'decimal:2',
    ];

    /**
     * Boot function to generate custom ID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = 'mv_' . Str::random(6) . substr(base_convert(time(), 10, 36), -4);
            }
            if (!isset($model->is_active)) {
                $model->is_active = true;
            }
            // Default duration to 90 minutes if not set
            if (empty($model->duration)) {
                $model->duration = 90;
            }
            // Default availability window: 3 weeks, capped at 1 month
            if (empty($model->available_until)) {
                $threeWeeks = now()->addWeeks(3);
                $oneMonth = now()->addMonth();
                $model->available_until = $threeWeeks->greaterThan($oneMonth) ? $oneMonth : $threeWeeks;
            }

            // Listing window defaults
            if (empty($model->listing_start)) {
                $model->listing_start = today();
            }
            if (empty($model->listing_end)) {
                // Prefer available_until if present; else 3 weeks capped 1 month
                $cap = now()->addMonth();
                $defaultEnd = ($model->available_until ?? now()->addWeeks(3));
                $model->listing_end = $defaultEnd->greaterThan($cap) ? $cap : $defaultEnd;
            }
        });
    }

    /**
     * Get all showtimes for this movie
     */
    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'movie_id', 'id')->orderBy('showtime');
    }

    /**
     * Get all booking items for this movie
     */
    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class, 'movie_id', 'id');
    }

    /**
     * Calculate and update total attendance from booking items
     */
    public function updateAttendance()
    {
        $totalAttendance = $this->bookingItems()
            ->whereHas('booking', function ($query) {
                $query->where('status', '!=', 'Cancelled');
            })
            ->whereDate('booking_date', '<=', today())
            ->sum('quantity');

        $this->update(['total_attendance' => $totalAttendance]);
        return $totalAttendance;
    }

    /**
     * Calculate and update view count (number of unique booking sessions)
     */
    public function updateViewCount()
    {
        $viewCount = $this->bookingItems()
            ->whereHas('booking', function ($query) {
                $query->where('status', '!=', 'Cancelled');
            })
            ->whereDate('booking_date', '<=', today())
            ->count();

        $this->update(['view_count' => $viewCount]);
        return $viewCount;
    }

    /**
     * Calculate and update popularity score
     * Formula: (total_attendance * 0.7) + (view_count * 0.2) + (recency_factor * 0.1)
     */
    public function updatePopularityScore()
    {
        $attendance = $this->total_attendance ?? 0;
        $views = $this->view_count ?? 0;

        $daysSinceCreated = now()->diffInDays($this->created_at);
        $recencyFactor = max(0, 100 - $daysSinceCreated);

        $popularityScore = ($attendance * 0.7) + ($views * 0.2) + ($recencyFactor * 0.1);

        $this->update(['popularity_score' => $popularityScore]);
        return $popularityScore;
    }

    /**
     * Update all metrics at once
     */
    public function updateMetrics()
    {
        $this->updateAttendance();
        $this->updateViewCount();
        $this->updatePopularityScore();
    }

    /**
     * Scope: Get movies that are currently showing
     */
    public function scopeNowShowing($query)
    {
        $today = today();
        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('listing_start')
                  ->orWhereDate('listing_start', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('listing_end')
                  ->orWhereDate('listing_end', '>=', $today);
            });
    }

    /**
     * Scope: Get movies coming soon
     */
    public function scopeComingSoon($query)
    {
        $today = today();
        return $query->where('is_active', true)
            ->whereNotNull('listing_start')
            ->whereDate('listing_start', '>', $today);
    }

    /**
     * Scope: Get most attended movies
     */
    public function scopeMostAttended($query)
    {
        return $query->where('is_active', true)
            ->orderByDesc('total_attendance');
    }

    /**
     * Scope: Get most popular movies
     */
    public function scopeMostPopular($query)
    {
        return $query->where('is_active', true)
            ->orderByDesc('popularity_score');
    }

    /**
     * Scope: Get top watched movies
     */
    public function scopeTopWatched($query)
    {
        return $query->where('is_active', true)
            ->orderByDesc('view_count');
    }
}
