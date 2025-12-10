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
}
