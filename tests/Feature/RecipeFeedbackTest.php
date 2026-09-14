<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;
use App\Support\Honeypot;
use App\Support\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
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
    protected function filled(array $fields = []): array
    {
        return [
            Honeypot::STAMP => Crypt::encryptString((string) (time() - 30)),
            ...$fields,
        ];
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

    public function test_two_people_both_count(): void
    {
        $url = route('recipes.rate', $this->recipe);

        // Two browsers, each arriving without a cookie of its own.
        $this->asSameVisitor($url, $this->filled(['stars' => 5]));
        $this->asSameVisitor($url, $this->filled(['stars' => 3]));

        $this->assertSame(2, $this->recipe->ratings()->count());
        $this->assertSame(4.0, round($this->recipe->ratings()->avg('stars'), 2));
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
            Honeypot::STAMP => Crypt::encryptString((string) time()),
        ])->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(0, $this->recipe->ratings()->count());
    }

    public function test_a_stale_form_is_turned_away(): void
    {
        // A day old is a replay, not a slow reader.
        $this->post(route('recipes.rate', $this->recipe), [
            'stars' => 5,
            Honeypot::STAMP => Crypt::encryptString((string) (time() - 86400)),
        ])->assertSessionHasErrors(Honeypot::STAMP);
    }

    public function test_a_forged_stamp_is_turned_away(): void
    {
        $this->post(route('recipes.rate', $this->recipe), [
            'stars' => 5,
            Honeypot::STAMP => (string) (time() - 30),
        ])->assertSessionHasErrors(Honeypot::STAMP);
    }

    public function test_a_comment_waits_to_be_read(): void
    {
        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex',
            'body' => 'Made this on the Skottle. Went down well.',
        ]))->assertRedirect();

        $comment = RecipeComment::sole();

        $this->assertSame(CommentStatus::Pending, $comment->status);
        $this->assertNull($comment->approved_at);
    }

    public function test_a_waiting_comment_is_not_on_the_page(): void
    {
        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex', 'body' => 'Waiting to be read.',
        ]));

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
        ]));

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
        ]))->assertRedirect();

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
        ]));

        $stored = Storage::disk('feedback-test')->get(RecipeComment::sole()->image->file->stored_path);

        $this->assertStringNotContainsString('GPSLatitudeMARKER', $stored);
    }

    public function test_something_that_is_not_a_photograph_is_refused(): void
    {
        $this->post(route('recipes.comment', $this->recipe), $this->filled([
            'name' => 'Alex',
            'body' => 'Try this.',
            'photo' => UploadedFile::fake()->create('payload.svg', 4, 'image/svg+xml'),
        ]))->assertSessionHasErrors('photo');

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_a_draft_recipe_takes_no_feedback(): void
    {
        $draft = Recipe::create(['name' => 'Secret', 'slug' => 'secret', 'is_draft' => true]);

        $this->post(route('recipes.rate', $draft), $this->filled(['stars' => 5]))->assertNotFound();
        $this->post(route('recipes.comment', $draft), $this->filled([
            'name' => 'Alex', 'body' => 'Sneaking in.',
        ]))->assertNotFound();
    }

    public function test_the_page_shows_the_stars_it_has(): void
    {
        foreach ([5, 4] as $i => $stars) {
            $this->recipe->ratings()->create([
                'stars' => $stars, 'visitor_hash' => "visitor-{$i}",
            ]);
        }

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.rating.count', 2)
                ->where('feedback.rating.average', 4.5)
                ->where('feedback.rating.stars.5', 1)
                ->where('feedback.rating.stars.4', 1)
                ->where('feedback.rating.stars.3', 0));
    }

    public function test_a_search_engine_is_only_told_about_a_rating_worth_showing(): void
    {
        config(['feedback.ratings.min_for_schema' => 3]);

        foreach ([5, 4] as $i => $stars) {
            $this->recipe->ratings()->create(['stars' => $stars, 'visitor_hash' => "visitor-{$i}"]);
        }

        // Two ratings. Shown on the page, not claimed in a search result.
        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.rating.count', 2)
                // Left out of the markup entirely rather than sent as null.
                ->missing('seo.schema.0.aggregateRating'));

        $this->recipe->ratings()->create(['stars' => 3, 'visitor_hash' => 'visitor-3']);

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
            $this->filled(['name' => 'Alex', 'body' => 'Best thing I have cooked out there.']),
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
        ]));

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

        foreach ([5, 4] as $i => $stars) {
            $this->recipe->ratings()->create(['stars' => $stars, 'visitor_hash' => "visitor-{$i}"]);
        }

        $this->get(route('recipes.index'))
            ->assertInertia(fn ($page) => $page->where('recipes.data.0.rating', null));

        $this->recipe->ratings()->create(['stars' => 3, 'visitor_hash' => 'visitor-3']);

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
        ]));

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
        ]));

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
        ]));

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
