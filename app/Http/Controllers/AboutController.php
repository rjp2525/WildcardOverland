<?php

namespace App\Http\Controllers;

use App\Support\SiteContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('About', [
            'stats' => SiteContent::stats(),
            'partners' => SiteContent::partners(),
            'timeline' => SiteContent::timeline(),
        ]);
    }
}
