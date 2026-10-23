<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class);
    }
}
