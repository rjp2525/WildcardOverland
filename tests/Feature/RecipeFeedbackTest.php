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
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reviews from people with no account: stars, words and a photograph.
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

    /** A whole review, on a form handed out long enough ago to have been filled in. */
    protected function review(array $fields = []): array
    {
        return [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'stars' => 5,
            'body' => 'Made this on the Skottle. Went down well.',
            Honeypot::STAMP => $this->stampFor('review'),
            ...$fields,
        ];
    }

    /** Handed out half a minute ago, without moving the clock for anything else. */
    protected function stampFor(string $purpose): string
    {
        return $this->travelTo(now()->subSeconds(30), fn () => Honeypot::stamp($purpose));
    }

    protected function url(): string
    {
        return route('recipes.review', $this->recipe);
    }

    /** Signs in and puts a review on the page, the way the admin screen does. */
    protected function approve(RecipeComment $review): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.comments.update', $review), [
                'status' => CommentStatus::Approved->value,
            ]);
    }

    public function test_a_review_takes_a_name_an_address_and_stars(): void
    {
        $this->post($this->url(), $this->review())->assertRedirect();

        $review = RecipeComment::sole();

        $this->assertSame('Alex', $review->name);
        $this->assertSame('alex@example.com', $review->email);
        $this->assertSame(5, $review->stars);
        $this->assertSame(CommentStatus::Pending, $review->status);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredFields(): array
    {
        return [
            'no name' => ['name'],
            'no email' => ['email'],
            'no stars' => ['stars'],
            'no words' => ['body'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function test_a_review_is_refused_without_every_part_of_it(string $missing): void
    {
        $fields = $this->review();
        unset($fields[$missing]);

        $this->post($this->url(), $fields)->assertSessionHasErrors($missing);

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_an_address_that_is_not_one_is_refused(): void
    {
        $this->post($this->url(), $this->review(['email' => 'alex@not a domain']))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_a_rating_outside_one_to_five_is_refused(): void
    {
        $this->post($this->url(), $this->review(['stars' => 9]))
            ->assertSessionHasErrors('stars');

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_nothing_counts_until_it_has_been_read(): void
    {
        $this->post($this->url(), $this->review(['stars' => 4]));

        // In the queue, so not on the page and not in the figure.
        $this->assertSame(0, $this->recipe->refresh()->rating_count);
        $this->assertNull($this->recipe->rating_average);

        $this->approve(RecipeComment::sole());

        $this->assertSame(1, $this->recipe->refresh()->rating_count);
        $this->assertSame(4.0, $this->recipe->rating_average);
    }

    public function test_taking_a_review_back_off_the_page_takes_its_stars_with_it(): void
    {
        $this->post($this->url(), $this->review(['stars' => 1]));

        $review = RecipeComment::sole();
        $this->approve($review);

        $this->assertSame(1, $this->recipe->refresh()->rating_count);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.comments.update', $review), [
                'status' => CommentStatus::Spam->value,
            ]);

        $this->assertSame(0, $this->recipe->refresh()->rating_count);
        $this->assertNull($this->recipe->rating_average);
    }

    public function test_deleting_a_review_takes_its_stars_with_it(): void
    {
        $this->post($this->url(), $this->review(['stars' => 5]));

        $review = RecipeComment::sole();
        $this->approve($review);

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.comments.destroy', $review));

        $this->assertSame(0, $this->recipe->refresh()->rating_count);
    }

    public function test_one_address_gets_one_review_per_recipe(): void
    {
        $this->post($this->url(), $this->review(['stars' => 5, 'body' => 'First go.']));

        $this->approve(RecipeComment::sole());

        // Back with second thoughts, from the same address.
        $this->post($this->url(), $this->review(['stars' => 2, 'body' => 'Second go, less good.']));

        $review = RecipeComment::sole();

        $this->assertSame(2, $review->stars);
        $this->assertSame('Second go, less good.', $review->body);

        /*
         * Rewritten, so it goes back into the queue. Otherwise a review is a
         * way to get words approved and then swap them for different ones.
         */
        $this->assertSame(CommentStatus::Pending, $review->status);
        $this->assertSame(0, $this->recipe->refresh()->rating_count);
    }

    public function test_the_same_address_in_different_case_is_the_same_person(): void
    {
        $this->post($this->url(), $this->review(['email' => 'alex@example.com']));
        $this->post($this->url(), $this->review(['email' => 'Alex@Example.COM']));

        $this->assertSame(1, RecipeComment::count());
    }

    public function test_two_people_both_count(): void
    {
        foreach ([['sam@example.com', 5], ['jo@example.com', 3]] as [$email, $stars]) {
            $this->post($this->url(), $this->review(['email' => $email, 'stars' => $stars]));
        }

        foreach (RecipeComment::all() as $review) {
            $this->approve($review);
        }

        $this->assertSame(2, $this->recipe->refresh()->rating_count);
        $this->assertSame(4.0, $this->recipe->rating_average);
    }

    public function test_an_address_is_never_sent_to_the_page(): void
    {
        $this->post($this->url(), $this->review(['email' => 'private@example.com']));

        $this->approve(RecipeComment::sole());

        $response = $this->get(route('recipes.show', $this->recipe->slug))->assertOk();

        $response->assertDontSee('private@example.com');
        $response->assertInertia(fn ($page) => $page
            ->has('feedback.comments', 1)
            ->missing('feedback.comments.0.email'));
    }

    public function test_a_filled_trap_is_turned_away(): void
    {
        // Nobody can see the field, so anything in it was not typed.
        $this->post($this->url(), $this->review([
            Honeypot::FIELD => 'https://cheap-pills.example',
        ]))->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_a_form_returned_instantly_is_turned_away(): void
    {
        $this->post($this->url(), $this->review([Honeypot::STAMP => Honeypot::stamp('review')]))
            ->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_a_stale_form_is_turned_away(): void
    {
        // A day old is a replay, not a slow reader.
        $stale = $this->travelTo(now()->subDay(), fn () => Honeypot::stamp('review'));

        $this->post($this->url(), $this->review([Honeypot::STAMP => $stale]))
            ->assertSessionHasErrors(Honeypot::STAMP);
    }

    public function test_a_forged_stamp_is_turned_away(): void
    {
        $this->post($this->url(), $this->review([Honeypot::STAMP => 'not-a-stamp-at-all']))
            ->assertSessionHasErrors(Honeypot::STAMP);
    }

    public function test_a_stamp_cannot_be_used_twice(): void
    {
        $stamp = $this->stampFor('review');

        $this->post($this->url(), $this->review([Honeypot::STAMP => $stamp]))->assertRedirect();

        // The same page, posted from again. One form, one submission.
        $this->post($this->url(), $this->review([
            'email' => 'someone-else@example.com',
            Honeypot::STAMP => $stamp,
        ]))->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(1, RecipeComment::count());
    }

    public function test_a_stamp_from_somewhere_else_does_not_work(): void
    {
        $this->post($this->url(), $this->review([Honeypot::STAMP => $this->stampFor('something-else')]))
            ->assertSessionHasErrors(Honeypot::STAMP);

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_one_address_cannot_sit_there_posting_all_day(): void
    {
        config(['feedback.throttle.reviews_per_hour' => 3]);

        foreach (range(1, 3) as $i) {
            $this->post($this->url(), $this->review(['email' => "person{$i}@example.com"]));
        }

        $this->post($this->url(), $this->review(['email' => 'person4@example.com']))
            ->assertStatus(429);

        $this->assertSame(3, RecipeComment::count());
    }

    public function test_a_waiting_review_is_not_on_the_page(): void
    {
        $this->post($this->url(), $this->review(['body' => 'Waiting to be read.']));

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('feedback.comments', 0))
            ->assertDontSee('Waiting to be read.', escape: false);
    }

    public function test_an_approved_review_is_on_the_page_with_its_stars(): void
    {
        $this->post($this->url(), $this->review(['stars' => 4, 'body' => 'Genuinely excellent.']));

        $this->approve(RecipeComment::sole());

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->has('feedback.comments', 1)
                ->where('feedback.comments.0.name', 'Alex')
                ->where('feedback.comments.0.stars', 4));
    }

    public function test_somebody_coming_back_is_told_their_review_is_in_the_queue(): void
    {
        $response = $this->post($this->url(), $this->review(['stars' => 4]));

        $cookie = $response->getCookie(Visitor::COOKIE)?->getValue();

        $this->withCookie(Visitor::COOKIE, $cookie)
            ->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.yours.stars', 4)
                ->where('feedback.yours.waiting', true));
    }

    public function test_somebody_who_has_not_been_here_is_told_nothing(): void
    {
        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page->where('feedback.yours', null));
    }

    public function test_a_photograph_stays_private_until_it_is_approved(): void
    {
        $this->post($this->url(), $this->review([
            'photo' => UploadedFile::fake()->image('mine.jpg', 1200, 900),
        ]))->assertRedirect();

        $review = RecipeComment::sole();

        $this->assertNotNull($review->image);
        $this->assertTrue($review->image->private, 'An unread photo must not be reachable.');

        $this->approve($review);

        $this->assertFalse($review->fresh()->image->private);
    }

    public function test_a_photograph_is_re_encoded_so_nothing_rides_along_in_it(): void
    {
        // A phone writes where it was standing into the file. Ours drop it
        // in the derivatives; a stranger's must not keep it in the original.
        $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
        $image = imagecreatetruecolor(800, 600);
        imagejpeg($image, $path, 90);
        file_put_contents($path, file_get_contents($path).'GPSLatitudeMARKER');

        $this->post($this->url(), $this->review([
            'photo' => new UploadedFile($path, 'mine.jpg', 'image/jpeg', null, true),
        ]));

        $stored = Storage::disk('feedback-test')->get(RecipeComment::sole()->image->file->stored_path);

        $this->assertStringNotContainsString('GPSLatitudeMARKER', $stored);
    }

    public function test_a_replaced_photograph_goes_back_out_of_reach(): void
    {
        $this->post($this->url(), $this->review([
            'photo' => UploadedFile::fake()->image('first.jpg', 800, 600),
        ]));

        $review = RecipeComment::sole();
        $this->approve($review);

        $first = $review->fresh()->image;
        $this->assertFalse($first->private);

        // Same address, second thoughts, different photograph.
        $this->post($this->url(), $this->review([
            // Different dimensions, so these are genuinely two photographs:
            // identical uploads are deduplicated into one stored file.
            'photo' => UploadedFile::fake()->image('second.jpg', 640, 480),
        ]));

        $this->assertTrue($first->fresh()->private, 'The old photo must not stay public.');
        $this->assertNotSame($first->id, $review->fresh()->image_id);
    }

    public function test_a_photograph_two_people_sent_in_is_not_hidden_by_one_of_them(): void
    {
        /*
         * Identical uploads are one stored file, so two reviews can point at
         * one photograph. Marking one of them as spam must not take it off
         * the other one's review.
         */
        foreach (['sam@example.com', 'jo@example.com'] as $email) {
            $this->post($this->url(), $this->review([
                'email' => $email,
                'photo' => UploadedFile::fake()->image('same.jpg', 800, 600),
            ]));
        }

        $reviews = RecipeComment::orderBy('id')->get();
        $this->assertSame($reviews[0]->image_id, $reviews[1]->image_id, 'Expected one stored file.');

        foreach ($reviews as $review) {
            $this->approve($review);
        }

        $this->actingAs(User::factory()->create())
            ->put(route('admin.comments.update', $reviews[0]), [
                'status' => CommentStatus::Spam->value,
            ]);

        $this->assertFalse(
            $reviews[1]->fresh()->image->private,
            'The other review is still on the page, so its photo must stay reachable.',
        );
    }

    public function test_something_that_is_not_a_photograph_is_refused(): void
    {
        $this->post($this->url(), $this->review([
            'photo' => UploadedFile::fake()->create('notes.pdf', 12, 'application/pdf'),
        ]))->assertSessionHasErrors('photo');

        $this->assertSame(0, RecipeComment::count());
    }

    public function test_a_draft_recipe_takes_no_feedback(): void
    {
        $draft = Recipe::create([
            'name' => 'Not ready', 'slug' => 'not-ready', 'is_draft' => true,
        ]);

        $this->post(route('recipes.review', $draft), $this->review())->assertNotFound();
    }

    public function test_the_page_shows_the_stars_it_has(): void
    {
        foreach ([['a@example.com', 5], ['b@example.com', 4]] as [$email, $stars]) {
            $this->post($this->url(), $this->review(['email' => $email, 'stars' => $stars]));
        }

        foreach (RecipeComment::all() as $review) {
            $this->approve($review);
        }

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
        $this->post($this->url(), $this->review(['email' => 'a@example.com', 'stars' => 5]));
        $this->approve(RecipeComment::sole());

        // A second one still in the queue must not show up in the bars.
        $this->post($this->url(), $this->review(['email' => 'b@example.com', 'stars' => 1]));

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.rating.count', 1)
                ->where('feedback.rating.stars.5', 1)
                ->where('feedback.rating.stars.1', 0));
    }

    public function test_a_search_engine_is_only_told_about_a_rating_worth_showing(): void
    {
        config(['feedback.ratings.min_for_schema' => 3]);

        foreach ([['a@example.com', 5], ['b@example.com', 4]] as [$email, $stars]) {
            $this->post($this->url(), $this->review(['email' => $email, 'stars' => $stars]));
        }

        foreach (RecipeComment::all() as $review) {
            $this->approve($review);
        }

        // Two reviews. Shown on the page, not claimed in a search result.
        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('feedback.rating.count', 2)
                // Left out of the markup entirely rather than sent as null.
                ->missing('seo.schema.0.aggregateRating'));

        $this->post($this->url(), $this->review(['email' => 'c@example.com', 'stars' => 3]));
        $this->approve(RecipeComment::where('email', 'c@example.com')->sole());

        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('seo.schema.0.aggregateRating.ratingValue', 4)
                ->where('seo.schema.0.aggregateRating.ratingCount', 3));
    }

    public function test_every_published_review_carries_its_rating(): void
    {
        $this->post($this->url(), $this->review([
            'stars' => 5, 'body' => 'Best thing I have cooked out there.',
        ]));

        $this->approve(RecipeComment::sole());

        /*
         * A Review with no reviewRating is the shape a search engine drops,
         * and every review has stars now that leaving them is part of
         * writing one.
         */
        $this->get(route('recipes.show', $this->recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('seo.schema.0.review.0.author.name', 'Alex')
                ->where('seo.schema.0.review.0.reviewRating.ratingValue', 5));
    }

    public function test_a_card_only_carries_stars_once_there_are_enough(): void
    {
        config(['feedback.ratings.min_for_schema' => 3]);

        foreach ([['a@example.com', 5], ['b@example.com', 4]] as [$email, $stars]) {
            $this->post($this->url(), $this->review(['email' => $email, 'stars' => $stars]));
        }

        foreach (RecipeComment::all() as $review) {
            $this->approve($review);
        }

        $this->get(route('recipes.index'))
            ->assertInertia(fn ($page) => $page->where('recipes.data.0.rating', null));

        $this->post($this->url(), $this->review(['email' => 'c@example.com', 'stars' => 3]));
        $this->approve(RecipeComment::where('email', 'c@example.com')->sole());

        $this->get(route('recipes.index'))
            ->assertInertia(fn ($page) => $page
                // 4, not 4.0: a whole average goes over the wire as one.
                ->where('recipes.data.0.rating.average', 4)
                ->where('recipes.data.0.rating.count', 3));
    }

    public function test_the_queue_is_not_open_to_the_public(): void
    {
        $this->post($this->url(), $this->review());

        $this->get(route('admin.comments.index'))->assertRedirect();

        $this->put(route('admin.comments.update', RecipeComment::sole()), [
            'status' => CommentStatus::Approved->value,
        ])->assertRedirect(route('admin.login'));

        $this->assertSame(CommentStatus::Pending, RecipeComment::sole()->status);
    }

    public function test_the_admin_sees_what_is_waiting(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post($this->url(), $this->review(['stars' => 3, 'body' => 'Waiting to be read.']));

        $this->get(route('admin.comments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/comments/Index')
                ->where('comments.data.0.name', 'Alex')
                ->where('comments.data.0.stars', 3)
                // The address, on this screen and nowhere else.
                ->where('comments.data.0.email', 'alex@example.com')
                ->where('comments.data.0.body', 'Waiting to be read.')
                ->where('moderation.pending', 1));
    }
}
