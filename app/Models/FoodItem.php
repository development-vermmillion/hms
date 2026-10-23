<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    use BelongsToHotel;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];
}
