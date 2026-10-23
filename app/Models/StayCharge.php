<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StayCharge extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
