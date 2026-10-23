<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomServiceOrder extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    public function stay(): BelongsTo { return $this->belongsTo(Stay::class); }
    public function items(): HasMany { return $this->hasMany(RoomServiceOrderItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function deliveredBy(): BelongsTo { return $this->belongsTo(User::class, 'delivered_by'); }
}
