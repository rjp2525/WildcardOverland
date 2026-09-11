<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Campsite;
use App\Models\File;
use App\Models\Image;
use App\Models\NavigationLink;
use App\Models\Trip;
use App\Models\VehicleModification;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                ['label' => 'Trips', 'value' => Trip::count(), 'route' => 'admin.trips.index'],
                ['label' => 'Campsites', 'value' => Campsite::count(), 'route' => null],
                ['label' => 'Modifications', 'value' => VehicleModification::count(), 'route' => 'admin.vehicle-modifications.index'],
                ['label' => 'Brands', 'value' => Brand::count(), 'route' => 'admin.brands.index'],
                ['label' => 'Images', 'value' => Image::count(), 'route' => 'admin.images.index'],
                ['label' => 'Files', 'value' => File::count(), 'route' => 'admin.files.index'],
                ['label' => 'Nav links', 'value' => NavigationLink::count(), 'route' => 'admin.navigation-links.index'],
            ],
            'recentTrips' => Trip::query()
                ->latest('updated_at')
                ->take(5)
                ->get(['id', 'name', 'is_draft', 'updated_at'])
                ->map(fn (Trip $trip) => [
                    'id' => $trip->id,
                    'name' => $trip->name,
                    'is_draft' => $trip->is_draft,
                    'updated_at' => $trip->updated_at?->diffForHumans(),
                ]),
        ]);
    }
}
