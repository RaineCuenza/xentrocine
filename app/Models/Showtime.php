<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Showtime extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'movie_id',
        'showtime',
        'end_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Store as strings, not datetime - the boot method handles formatting
    ];

    /**
     * Get the movie that owns this showtime
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Normalize start time to H:i and compute end_time from movie duration if not provided
            if (!empty($model->showtime)) {
                try {
                    // Try to parse as H:i format first
                    if (preg_match('/^\d{1,2}:\d{2}$/', $model->showtime)) {
                        $start = \Carbon\Carbon::createFromFormat('H:i', $model->showtime);
                    } else {
                        // Fallback to strtotime if format doesn't match
                        $timestamp = strtotime($model->showtime);
                        if ($timestamp === false) {
                            throw new \Exception("Invalid time format: {$model->showtime}");
                        }
                        $start = \Carbon\Carbon::createFromFormat('H:i', date('H:i', $timestamp));
                    }

                    $model->showtime = $start->format('H:i');
                    if (empty($model->end_time)) {
                        $duration = $model->movie?->duration ?? 90;
                        $model->end_time = $start->copy()->addMinutes($duration)->format('H:i');
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to parse showtime: " . $e->getMessage());
                    // Set a default time if parsing fails
                    $model->showtime = '00:00';
                    $model->end_time = '02:00';
                }
            }
        });

        static::updating(function ($model) {
            if (!empty($model->showtime)) {
                try {
                    // Try to parse as H:i format first
                    if (preg_match('/^\d{1,2}:\d{2}$/', $model->showtime)) {
                        $start = \Carbon\Carbon::createFromFormat('H:i', $model->showtime);
                    } else {
                        // Fallback to strtotime if format doesn't match
                        $timestamp = strtotime($model->showtime);
                        if ($timestamp === false) {
                            throw new \Exception("Invalid time format: {$model->showtime}");
                        }
                        $start = \Carbon\Carbon::createFromFormat('H:i', date('H:i', $timestamp));
                    }

                    $model->showtime = $start->format('H:i');
                    // Recompute end_time when changing start or when end_time missing
                    if (empty($model->end_time) || $model->isDirty('showtime')) {
                        $duration = $model->movie?->duration ?? 90;
                        $model->end_time = $start->copy()->addMinutes($duration)->format('H:i');
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to parse showtime: " . $e->getMessage());
                }
            }
        });
    }

    /**
     * Accessor: formatted_range => "HH:MM–HH:MM"
     */
    public function getFormattedRangeAttribute(): string
    {
        $start = $this->showtime ? date('H:i', strtotime($this->showtime)) : '';
        $end = $this->end_time ? date('H:i', strtotime($this->end_time)) : '';
        return $start && $end ? $start . '–' . $end : $start;
    }
}
