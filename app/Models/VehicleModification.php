<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'vendor',
        'purchased_from',
        'description',
        'purchase_date',
        'install_date',
        'cost',
        'url',
        'shown_on_timeline',
    ];
}
