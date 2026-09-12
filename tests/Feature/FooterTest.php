<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The footer lives in the layout, so its data has to reach every page,
 * including the ones rendered from the exception handler where the Inertia
 * middleware never runs.
 */
class FooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_data_reaches_every_page_including_errors(): void
    {
        config([
            'site.social' => ['instagram' => 'https://instagram.com/example', 'youtube' => null, 'tiktok' => null],
            'site.email' => 'hello@example.test',
            'site.since' => 2024,
        ]);

        foreach ([route('homepage'), route('about'), '/no-such-trail'] as $url) {
            $this->get($url)->assertInertia(fn ($page) => $page
                ->where('site.social.instagram', 'https://instagram.com/example')
                ->where('site.email', 'hello@example.test')
                ->where('site.since', 2024));
        }
    }

    public function test_a_handle_that_is_not_set_is_left_out_rather_than_linked_empty(): void
    {
        config(['site.social' => ['instagram' => null, 'youtube' => null, 'tiktok' => null]]);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page->has('site.social', 0));
    }
}
