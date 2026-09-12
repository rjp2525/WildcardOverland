<?php

namespace App\Models;

use App\Observers\CampsiteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(CampsiteObserver::class)]
class Campsite extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'order',
        'name',
        'latitude',
        'longitude',
        'state',
        'country_code',
        'geocoded_at',
        'nights',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'nights' => 'integer',
            'order' => 'integer',
            'geocoded_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Coordinates present but never successfully resolved to a region.
     */
    public function needsGeocoding(): bool
    {
        return $this->hasCoordinates() && $this->state === null;
    }
}
