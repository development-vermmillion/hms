<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    protected $casts = ['paid_at' => 'datetime', 'amount' => 'decimal:2'];

    public function stay(): BelongsTo { return $this->belongsTo(Stay::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}
