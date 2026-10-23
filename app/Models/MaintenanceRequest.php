<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    protected $casts = ['blocks_room' => 'boolean', 'resolved_at' => 'datetime'];

    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
