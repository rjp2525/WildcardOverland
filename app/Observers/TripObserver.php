<?php

namespace App\Observers;

use App\Models\Trip;

class TripObserver
{
    public function creating(Trip $trip): void
    {
        if ($trip->start_date && $trip->end_date) {
            $trip->calculated_nights = (int) abs($trip->end_date->diffInDays($trip->start_date));
        }
    }

    public function updated(Trip $trip): void
    {
        if ($trip->start_date && $trip->end_date) {
            $trip->calculated_nights = (int) abs($trip->end_date->diffInDays($trip->start_date));
            $trip->saveQuietly();
        }
    }
}
