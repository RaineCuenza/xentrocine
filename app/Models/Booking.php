<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
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
        'user_id',
        'name',
        'email',
        'phone',
        'reference',
        'total',
        'status',
        'payment_method',
        'et_qr_token',
        'entry_scanned_at',
        'checked_in_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total' => 'decimal:2',
        'entry_scanned_at' => 'datetime',
    ];

    /**
     * Boot function to generate custom ID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = 'bk_' . Str::random(6) . substr(base_convert(time(), 10, 36), -4);
            }
        });
    }

    /**
     * Get the user that owns this booking
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get all booking items (seat reservations) for this booking
     */
    public function items()
    {
        return $this->hasMany(BookingItem::class, 'booking_id', 'id');
    }

    /**
     * Check if booking is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'Paid';
    }

    /**
     * Check if booking is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'Cancelled';
    }

    /**
     * Check if booking is checked in
     */
    public function isCheckedIn(): bool
    {
        return !is_null($this->entry_scanned_at);
    }

    /**
     * Check if payment is cash
     */
    public function isCashPayment(): bool
    {
        return $this->payment_method === 'cash';
    }

    /**
     * Check if payment is digital
     */
    public function isDigitalPayment(): bool
    {
        return $this->payment_method === 'digital';
    }

    /**
     * Get the user who checked in this booking
     */
    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by', 'id');
    }
}
