<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stay extends Model
{
    use BelongsToHotel;

    public const RESERVED = 'reserved';
    public const CHECKED_IN = 'checked_in';
    public const CHECKED_OUT = 'checked_out';
    public const CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'arrival_date' => 'date',
        'check_in_at' => 'datetime',
        'expected_check_out' => 'date',
        'check_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rate' => 'decimal:2',
    ];

    public function guest(): BelongsTo { return $this->belongsTo(Guest::class); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function invoice(): HasOne { return $this->hasOne(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function orders(): HasMany { return $this->hasMany(RoomServiceOrder::class); }
    public function charges(): HasMany { return $this->hasMany(StayCharge::class); }
    public function amenities(): HasMany { return $this->hasMany(StayAmenity::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function checkedInBy(): BelongsTo { return $this->belongsTo(User::class, 'checked_in_by'); }
    public function checkedOutBy(): BelongsTo { return $this->belongsTo(User::class, 'checked_out_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }

    /** Billable nights (minimum 1). In-house stays bill to the expected check-out, or today if later. */
    public function nights(): int
    {
        $start = ($this->check_in_at ?? $this->arrival_date)->copy()->startOfDay();

        if ($this->check_out_at) {
            $end = $this->check_out_at->copy();
        } else {
            $end = now()->greaterThan($this->expected_check_out) ? now() : $this->expected_check_out->copy();
        }

        return max(1, (int) $start->diffInDays($end->startOfDay(), true));
    }

    public function roomCharges(): float
    {
        return round($this->nights() * (float) $this->rate, 2);
    }

    public function foodTotal(): float
    {
        return (float) $this->orders()->where('status', '!=', 'cancelled')->sum('total');
    }

    public function extrasTotal(): float
    {
        return (float) $this->charges()->sum('amount');
    }

    public function amenityPenalty(): float
    {
        return (float) $this->amenities()->sum('penalty_amount');
    }

    public function paidTotal(): float
    {
        return (float) $this->payments()->sum('amount');
    }
}
