<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('About', [
            'seo' => Seo::make(
                title: 'About',
                description: 'Who is driving, what the truck is, and why any of this ended up on the internet. Reno, a 2021 Tacoma and a German Shepherd called Maya.',
            ),
            'stats' => SiteContent::stats(),
            'partners' => SiteContent::partners(),
            'timeline' => SiteContent::timeline(),
        ]);
    }
}
