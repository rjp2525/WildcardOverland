<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Saving a campsite with coordinates dispatches a reverse-geocode, so
         * the suite would otherwise reach Nominatim. Off by default; the
         * geocoding tests opt back in and fake the HTTP themselves.
         */
        config(['geocoding.enabled' => false]);

        // Anything that does slip through fails loudly rather than silently
        // making a real request.
        Http::preventStrayRequests();
    }
}
