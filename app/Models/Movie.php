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
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
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
