<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Enums\RatingStatus;
use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;
use App\Support\Honeypot;
use App\Support\Ratings;
use App\Support\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ratings, comments and photographs from people with no account.
 *
 * The whole surface is open to the internet, so most of what is checked here
 * is what happens when it is not a person on the other end.
 */
class RecipeFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected Recipe $recipe;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'feedback-test']);
        Storage::fake('feedback-test');

        $this->recipe = Recipe::create([
            'name' => 'Camp cowboy stew',
            'slug' => 'camp-cowboy-stew',
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);
    }

    /** A form handed out long enough ago that a person could have filled it. */
    protected function filled(array $fields = [], string $purpose = 'rating'): array
    {
        return [
            Honeypot::STAMP => $this->stampFor($purpose),
            ...$fields,
        ];
    }

    /**
     * Ratings put straight into the database, then added up.
     *
     * The published figure is stored on the recipe, so anything that writes
     * ratings without going through the controller has to say so - which is
     * the point of it being stored rather than worked out on each read.
     */
    protected function giveStars(array $stars, ?string $address = null): void
    {
        foreach ($stars as $i => $count) {
            $this->recipe->ratings()->create([
                'stars' => $count,
                'visitor_hash' => "visitor-{$i}-".Str::random(6),
                'ip_hash' => $address,
            ]);
        }

        Ratings::recount($this->recipe->refresh());
    }

    /** Handed out half a minute ago, without moving the clock for anything else. */
    protected function stampFor(string $purpose): string
    {
        return $this->travelTo(now()->subSeconds(30), fn () => Honeypot::stamp($purpose));
    }

    /**
     * Posts the way a browser would, keeping the visitor cookie the last
     * response handed back. Without this every request is a new person,
     * which is not what happens to anybody real.
     */
    protected function asSameVisitor(string $url, array $data, ?string $token = null): array
    {
        $response = $token === null
            ? $this->post($url, $data)
            : $this->withCookie(Visitor::COOKIE, $token)->post($url, $data);

        $cookie = $response->getCookie(Visitor::COOKIE);

        return [$response, $cookie?->getValue() ?? $token];
    }

    public function test_someone_can_rate_without_an_account(): void
    {
        $this->post(route('recipes.rate', $this->recipe), $this->filled(['stars' => 5]))
            ->assertRedirect();

        $this->assertSame(1, $this->recipe->ratings()->count());
        $this->assertSame(5, $this->recipe->ratings()->sole()->stars);
    }

    public function test_changing_your_mind_replaces_your_rating(): void
    {
        $url = route('recipes.rate', $this->recipe);

        [, $token] = $this->asSameVisitor($url, $this->filled(['stars' => 5]));
        $this->asSameVisitor($url, $this->filled(['stars' => 2]), $token);

        // Same browser, so one rating, not an average of two.
        $this->assertSame(1, $this->recipe->ratings()->count());
        $this->assertSame(2, $this->recipe->ratings()->sole()->stars);
    }

    public function test_clearing_your_cookies_does_not_buy_a_second_vote(): void
    {
        $url = route('recipes.rate', $this->recipe);

        // Two browsers from one address, which is what one person looks
        // like after they have thrown their cookies away.
        $this->asSameVisitor($url, $this->filled(['stars' => 5]));
        $this->asSameVisitor($url, $this->filled(['stars' => 1]));

        // Both kept, one counted, and the published figure untouched.
        $this->assertSame(2, $this->recipe->ratings()->count());
        $this->assertSame(1, $this->recipe->ratings()->counted()->count());
        $this->assertSame(5.0, $this->recipe->refresh()->rating_average);
        $this->assertSame(1, $this->recipe->rating_count);
    }

    public function test_two_people_at_different_addresses_both_count(): void
    {
        $url = route('recipes.rate', $this->recipe);

        $this->asSameVisitor($url, $this->filled(['stars' => 5]));

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post($url, $this->filled(['stars' => 3]));

        $this->assertSame(2, $this->recipe->ratings()->counted()->count());
        $this->assertSame(4.0, $this->recipe->refresh()->rating_average);
    }

    public function test_a_run_of_ratings_on_one_recipe_is_held_back(): void
    {
        config(['feedback.ratings.burst_limit' => 3, 'feedback.ratings.burst_minutes' => 60]);

        $url = route('recipes.rate', $this->recipe);

        /*
         * Every one from a different address, so the address rule is not
         * what catches them. This is the shape of a rating being bought.
         */
        foreach (range(1, 6) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$i}"])
                ->post($url, $this->filled(['stars' => 5]));
        }

        $this->assertSame(6, $this->recipe->ratings()->count());
        $this->assertSame(3, $this->recipe->ratings()->counted()->count());
        $this->assertSame(3, $this->recipe->refresh()->rating_count);
    }

    public function test_a_held_rating_can_be_let_through_by_hand(): void
    {
        $this->actingAs(User::factory()->create());

        $url = route('recipes.rate', $this->recipe);

        $this->asSameVisitor($url, $this->filled(['stars' => 5]));
        $this->asSameVisitor($url, $this->filled(['stars' => 3]));

        $held = $this->recipe->ratings()->where('status', RatingStatus::Held)->sole();

        $this->put(route('admin.ratings.update', $held), [
            'status' => RatingStatus::Counted->value,
        ])->assertRedirect();

        // Counted, and the recipe added up again on the way out.
        $this->assertSame(2, $this->recipe->refresh()->rating_count);
        $this->assertSame(4.0, $this->recipe->rating_average);
    }

    public function test_throwing_a_rating_out_takes_it_off_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->asSameVisitor(route('recipes.rate', $this->recipe), $this->filled(['stars' => 1]));

        $rating = $this->recipe->ratings()->sole();

        $this->put(route('admin.ratings.update', $rating), [
            'status' => RatingStatus::Discounted->value,
        ]);

        $this->assertSame(0, $this->recipe->refresh()->rating_count);
        $this->assertNull($this->recipe->rating_average);
    }

    public function test_a_stamp_cannot_be_used_twice(): void
    {
        $url = route('recipes.rate', $this->recipe);
        $stamp = $this->stampFor('rating');

        $this->post($url, ['stars' => 5, Honeypot::STAMP => $stamp])->assertRedirect();

        // The same page, posted from again. One form, one submission.
        $this->post($url, ['stars' => 1, Honeypot::STAMP => $stamp])
            ->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(5, $this->recipe->ratings()->sole()->stars);
    }

    public function test_a_stamp_from_one_form_does_not_work_on_the_other(): void
    {
        // A stamp handed out with the comment box, posted to the rating route.
        $this->post(route('recipes.rate', $this->recipe), [
            'stars' => 5,
            Honeypot::STAMP => $this->stampFor('comment'),
        ])->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(0, $this->recipe->ratings()->count());
    }

    public function test_the_admin_sees_what_was_held_back_and_why(): void
    {
        $this->actingAs(User::factory()->create());

        $url = route('recipes.rate', $this->recipe);

        $this->asSameVisitor($url, $this->filled(['stars' => 5]));
        $this->asSameVisitor($url, $this->filled(['stars' => 1]));

        $this->get(route('admin.ratings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/ratings/Index')
                ->has('ratings.data', 1)
                ->where('ratings.data.0.stars', 1)
                ->where('ratings.data.0.status', RatingStatus::Held->value)
                // What the recipe is publishing without it.
                ->where('ratings.data.0.published.count', 1)
                // And the reason it is here at all.
                ->where('ratings.data.0.alsoFromAddress', 1)
                ->where('moderation.held', 1));
    }

    public function test_the_ratings_queue_is_not_open_to_the_public(): void
    {
        $this->asSameVisitor(route('recipes.rate', $this->recipe), $this->filled(['stars' => 5]));

        $this->get(route('admin.ratings.index'))->assertRedirect();

        $this->put(route('admin.ratings.update', $this->recipe->ratings()->sole()), [
            'status' => RatingStatus::Discounted->value,
        ])->assertRedirect(route('admin.login'));

        $this->assertSame(1, $this->recipe->refresh()->rating_count);
    }

    public function test_one_address_cannot_sit_there_rating_all_day(): void
    {
        /*
         * The cookie is what keeps an honest visitor to one rating, and
         * something that drops cookies gets a new identity every time. The
         * throttle is what binds that, so it is worth knowing it is on.
         */
        config(['feedback.throttle.ratings_per_hour' => 3]);

        $url = route('recipes.rate', $this->recipe);

        foreach (range(1, 3) as $i) {
            $this->asSameVisitor($url, $this->filled(['stars' => 5]));
        }

        $this->asSameVisitor($url, $this->filled(['stars' => 5]))[0]
            ->assertStatus(429);

        $this->assertSame(3, $this->recipe->ratings()->count());
    }

    public function test_a_rating_outside_one_to_five_is_refused(): void
    {
        foreach ([0, 6, -1, 99] as $stars) {
            $this->post(route('recipes.rate', $this->recipe), $this->filled(['stars' => $stars]))
                ->assertSessionHasErrors('stars');
        }

        $this->assertSame(0, $this->recipe->ratings()->count());
    }

    public function test_a_filled_trap_is_turned_away(): void
    {
        // Nobody can see the field, so anything in it was not typed.
        $this->post(route('recipes.rate', $this->recipe), $this->filled([
            'stars' => 5,
            Honeypot::FIELD => 'https://cheap-pills.example',
        ]))->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(0, $this->recipe->ratings()->count());
    }

    public function test_a_form_returned_instantly_is_turned_away(): void
    {
        $this->post(route('recipes.rate', $this->recipe), [
            'stars' => 5,
            Honeypot::STAMP => Honeypot::stamp('rating'),
        ])->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(0, $this->recipe->ratings()->count());
    }

    public function test_a_stale_form_is_turned_away(): void
    {
        // A day old is a replay, not a slow reader.
        $stale = $this->travelTo(now()->subDay(), fn () => Honeypot::stamp('rating'));

        $this->post(route('recipes.rate', $this->recipe), [
            'stars' => 5,
            Honeypot::STAMP => $stale,
        ])->assertSessionHasErrors(Honeypot::STAMP);
    }

    public function test_a_forged_stamp_is_turned_away(): void
    {
        $this->post(route('recipes.rate', $this->recipe), [
            'stars' => 5,
            Honeypot::STAMP => 'not-a-stamp-at-all',
        ])->assertSessionHasErrors(Honeypot::STAMP);
    }

    public function test_a_comment_waits_to_be_read(): void
    {
        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex',
            'body' => 'Made this on the Skottle. Went down well.',
        ], 'comment'))->assertRedirect();

        $comment = RecipeComment::sole();

        $this->assertSame(CommentStatus::Pending, $comment->status);
        $this->assertNull($comment->approved_at);
    }

    public function test_a_waiting_comment_is_not_on_the_page(): void
    {
        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex', 'body' => 'Waiting to be read.',
        ], 'comment'));

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('feedback.comments', 0))
            ->assertDontSee('Waiting to be read.', escape: false);
    }

    public function test_an_approved_comment_is_on_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex', 'body' => 'This is genuinely excellent.',
        ], 'comment'));

        $this->put(route('admin.comments.update', RecipeComment::sole()), [
            'status' => CommentStatus::Approved->value,
        ])->assertRedirect();

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->has('feedback.comments', 1)
                ->where('feedback.comments.0.name', 'Alex'));
    }

    public function test_a_photograph_stays_private_until_it_is_approved(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex',
            'body' => 'Here is how mine came out.',
            'photo' => UploadedFile::fake()->image('mine.jpg', 1200, 900),
        ], 'comment'))->assertRedirect();

        $comment = RecipeComment::sole();

        $this->assertNotNull($comment->image);
        $this->assertTrue($comment->image->private, 'An unread photo must not be reachable.');

        $this->put(route('admin.comments.update', $comment), [
            'status' => CommentStatus::Approved->value,
        ]);

        $this->assertFalse($comment->fresh()->image->private);
    }

    public function test_a_photograph_is_re_encoded_so_nothing_rides_along_in_it(): void
    {
        // A phone writes where it was standing into the file. Ours drop it
        // in the derivatives; a stranger's must not keep it in the original.
        $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
        $image = imagecreatetruecolor(800, 600);
        imagejpeg($image, $path, 90);
        $withExif = file_get_contents($path).'GPSLatitudeMARKER';
        file_put_contents($path, $withExif);

        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex',
            'body' => 'From the campsite.',
            'photo' => new UploadedFile($path, 'mine.jpg', 'image/jpeg', null, true),
        ], 'comment'));

        $stored = Storage::disk('feedback-test')->get(RecipeComment::sole()->image->file->stored_path);

        $this->assertStringNotContainsString('GPSLatitudeMARKER', $stored);
    }

    public function test_something_that_is_not_a_photograph_is_refused(): void
    {
        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex',
            'body' => 'Try this.',
            'photo' => UploadedFile::fake()->create('payload.svg', 4, 'image/svg+xml'),
        ], 'comment'))->assertSessionHasErrors('photo');

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_a_draft_recipe_takes_no_feedback(): void
    {
        $draft = Recipe::create(['name' => 'Secret', 'slug' => 'secret', 'is_draft' => true]);

        $this->post(route('recipes.rate', $draft), $this->filled(['stars' => 5]))->assertNotFound();
        $this->post(route('recipes.comment', $draft), $this->filled([
            'name' => 'Alex', 'body' => 'Sneaking in.',
        ], 'comment'))->assertNotFound();
    }

    public function test_the_page_shows_the_stars_it_has(): void
    {
        $this->giveStars([5, 4]);

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.rating.count', 2)
                ->where('feedback.rating.average', 4.5)
                ->where('feedback.rating.stars.5', 1)
                ->where('feedback.rating.stars.4', 1)
                ->where('feedback.rating.stars.3', 0));
    }

    public function test_the_spread_on_the_page_only_counts_what_counts(): void
    {
        $url = route('recipes.rate', $this->recipe);

        // One that counts, then a one-star from the same address, held.
        $this->asSameVisitor($url, $this->filled(['stars' => 5]));
        $this->asSameVisitor($url, $this->filled(['stars' => 1]));

        /*
         * The bars are drawn from these buckets, so a held rating showing up
         * in them would put a number on the page that the average, the count
         * and the markup all disagree with.
         */
        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.rating.count', 1)
                ->where('feedback.rating.stars.5', 1)
                ->where('feedback.rating.stars.1', 0));
    }

    public function test_a_search_engine_is_only_told_about_a_rating_worth_showing(): void
    {
        config(['feedback.ratings.min_for_schema' => 3]);

        $this->giveStars([5, 4]);

        // Two ratings. Shown on the page, not claimed in a search result.
        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.rating.count', 2)
                // Left out of the markup entirely rather than sent as null.
                ->missing('seo.schema.0.aggregateRating'));

        $this->giveStars([3]);

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('seo.schema.0.aggregateRating.ratingValue', 4)
                ->where('seo.schema.0.aggregateRating.ratingCount', 3));
    }

    public function test_a_comment_with_stars_behind_it_is_published_as_a_review(): void
    {
        $this->actingAs(User::factory()->create());

        $url = route('recipes.rate', $this->recipe);

        [, $token] = $this->asSameVisitor($url, $this->filled(['stars' => 5]));

        $this->withCookie(Visitor::COOKIE, $token)->post(
            route('recipes.comment', $this->recipe),
            $this->filled(['name' => 'Alex', 'body' => 'Best thing I have cooked out there.'], 'comment'),
        );

        $this->put(route('admin.comments.update', RecipeComment::sole()), [
            'status' => CommentStatus::Approved->value,
        ]);

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('seo.schema.0.review.0.author.name', 'Alex')
                ->where('seo.schema.0.review.0.reviewRating.ratingValue', 5)
                // And the page shows the same stars beside their words.
                ->where('feedback.comments.0.stars', 5));
    }

    public function test_a_comment_from_somebody_who_did_not_rate_is_not_a_review(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex', 'body' => 'No stars from me, just a note.',
        ], 'comment'));

        $this->put(route('admin.comments.update', RecipeComment::sole()), [
            'status' => CommentStatus::Approved->value,
        ]);

        /*
         * A Review with no reviewRating is the shape a search engine drops,
         * and inventing a number to fill it would be lying about them.
         */
        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->missing('seo.schema.0.review')
                ->where('feedback.comments.0.stars', null));
    }

    public function test_a_card_only_carries_stars_once_there_are_enough(): void
    {
        config(['feedback.ratings.min_for_schema' => 3]);

        $this->giveStars([5, 4]);

        $this->get(route('recipes.index'))
            ->assertInertia(fn ($page) => $page->where('recipes.data.0.rating', null));

        $this->giveStars([3]);

        $this->get(route('recipes.index'))
            ->assertInertia(fn ($page) => $page
                // 4, not 4.0: a whole average goes over the wire as one.
                ->where('recipes.data.0.rating.average', 4)
                ->where('recipes.data.0.rating.count', 3));
    }

    public function test_marking_a_comment_as_spam_takes_it_back_off_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex',
            'body' => 'Cheap watches, click here.',
            'photo' => UploadedFile::fake()->image('mine.jpg', 800, 600),
        ], 'comment'));

        $comment = RecipeComment::sole();

        $this->put(route('admin.comments.update', $comment), [
            'status' => CommentStatus::Approved->value,
        ]);

        $this->put(route('admin.comments.update', $comment), [
            'status' => CommentStatus::Spam->value,
        ]);

        // Off the page, and the photograph unreachable again with it.
        $this->assertTrue($comment->fresh()->image->private);

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page->has('feedback.comments', 0));
    }

    public function test_the_moderation_queue_is_not_open_to_the_public(): void
    {
        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex', 'body' => 'Waiting.',
        ], 'comment'));

        $this->get(route('admin.comments.index'))->assertRedirect();

        $this->put(route('admin.comments.update', RecipeComment::sole()), [
            'status' => CommentStatus::Approved->value,
        ])->assertRedirect(route('admin.login'));

        $this->assertSame(CommentStatus::Pending, RecipeComment::sole()->status);
    }

    public function test_the_admin_sees_what_is_waiting(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex', 'body' => 'Waiting to be read.',
        ], 'comment'));

        $this->get(route('admin.comments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/comments/Index')
                ->where('comments.data.0.name', 'Alex')
                ->where('comments.data.0.body', 'Waiting to be read.')
                // And the nav badge that stops it sitting there unread.
                ->where('moderation.pending', 1));
    }
}
