<?php

namespace App\Models;

use App\Observers\TripObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(TripObserver::class)]
class Trip extends Model
{
    use HasFactory;
}
