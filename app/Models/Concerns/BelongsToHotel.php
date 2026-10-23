<?php

namespace App\Models\Concerns;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-hotel tenancy: every query is limited to the logged-in user's hotel,
 * and new rows are stamped with that hotel automatically.
 */
trait BelongsToHotel
{
    protected static function bootBelongsToHotel(): void
    {
        static::addGlobalScope('hotel', function (Builder $builder) {
            if (auth()->hasUser()) {
                $builder->where($builder->getModel()->getTable().'.hotel_id', auth()->user()->hotel_id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->hotel_id) && auth()->hasUser()) {
                $model->hotel_id = auth()->user()->hotel_id;
            }
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
