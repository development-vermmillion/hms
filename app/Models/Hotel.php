<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Hotel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tax_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Next sequential invoice number for this hotel, e.g. INV-000012. */
    public function nextInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $hotel = static::whereKey($this->id)->lockForUpdate()->first();
            $hotel->increment('invoice_seq');

            return $hotel->invoice_prefix.str_pad((string) $hotel->invoice_seq, 6, '0', STR_PAD_LEFT);
        });
    }
}
