<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use BelongsToHotel;

    public const AVAILABLE = 'available';
    public const OCCUPIED = 'occupied';
    public const RESERVED = 'reserved';
    public const CLEANING = 'cleaning';
    public const MAINTENANCE = 'maintenance';

    public const STATUSES = [self::AVAILABLE, self::OCCUPIED, self::RESERVED, self::CLEANING, self::MAINTENANCE];

    protected $guarded = [];

    public function type(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class);
    }

    /** The stay currently in the room, if any. */
    public function currentStay(): HasOne
    {
        return $this->hasOne(Stay::class)->where('status', Stay::CHECKED_IN)->latestOfMany();
    }
}
