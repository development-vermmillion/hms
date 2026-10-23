<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class AmenityItem extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];
}
