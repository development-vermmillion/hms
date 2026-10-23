<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StayAmenity extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    protected $casts = ['checked_at' => 'datetime'];

    public function stay(): BelongsTo { return $this->belongsTo(Stay::class); }
    public function item(): BelongsTo { return $this->belongsTo(AmenityItem::class, 'amenity_item_id'); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function checker(): BelongsTo { return $this->belongsTo(User::class, 'checked_by'); }

    public function missing(): int
    {
        return $this->qty_returned === null ? 0 : max(0, $this->qty_issued - $this->qty_returned);
    }
}
