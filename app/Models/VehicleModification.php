<?php

namespace App\Models;

use App\Enums\BuildLayer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModification extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'build_layer' => BuildLayer::class,
            'hotspot_x' => 'float',
            'hotspot_y' => 'float',
            'shown_on_timeline' => 'boolean',
        ];
    }

    /** Placed on the build illustration, so it can be shown as a hotspot. */
    public function isPlaced(): bool
    {
        return $this->hotspot_x !== null && $this->hotspot_y !== null;
    }

    /** Where to send someone who wants to buy it. */
    public function buyUrl(): ?string
    {
        return $this->affiliate_url ?: $this->url;
    }

    protected $fillable = [
        'name',
        'vendor',
        'purchased_from',
        'description',
        'purchase_date',
        'install_date',
        'cost',
        'url',
        'affiliate_url',
        'hotspot_x',
        'hotspot_y',
        'build_layer',
        'shown_on_timeline',
    ];
}
