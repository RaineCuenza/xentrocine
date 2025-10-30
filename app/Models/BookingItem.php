<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'booking_id',
        'movie_id',
        'movie_title',
        'booking_date',
        'showtime',
        'cinema',
        'seat_row',
        'seat_col',
        'seat_key',
        'price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_date' => 'date',
        'showtime' => 'datetime:H:i',
        'price' => 'decimal:2',
    ];

    /**
     * Get the booking that owns this item
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    /**
     * Get the movie for this booking item
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    /**
     * Get the session identifier (for seat availability checking)
     */
    public function getSessionIdAttribute(): string
    {
        return "{$this->movie_id}|{$this->booking_date}|{$this->showtime}|{$this->cinema}";
    }
}
