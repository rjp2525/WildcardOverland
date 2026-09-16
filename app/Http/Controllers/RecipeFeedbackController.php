<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Enums\ImageType;
use App\Http\Requests\Feedback\ReviewRequest;
use App\Mail\ConfirmReview;
use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Services\FileUploadService;
use App\Support\Ratings;
use App\Support\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * What people who cooked it send back.
 *
 * One form and one row: a name, an address to reach them at, stars, what
 * they thought, and a photograph if they took one. Nobody signs in, so the
 * whole thing is open to the internet and everything about it assumes so.
 *
 * Four things do that work. A honeypot on the form, a throttle on the route,
 * an address that has to be given before stars can be left at all, and a
 * queue that everything sits in until somebody has read it. Nothing reaches
 * the page or the published average before that last one.
 */
class RecipeFeedbackController extends Controller
{
    public function review(
        ReviewRequest $request,
        Recipe $recipe,
        FileUploadService $uploads,
    ): RedirectResponse {
        abort_unless($this->isPublished($recipe), 404);

        /*
         * Lower-cased so that coming back as Alex@ rather than alex@ is
         * recognised as the same person rather than counted as a second one.
         */
        $email = Str::lower($request->string('email')->trim()->value());

        $moderate = (bool) config('feedback.comments.moderate');

        $confirm = (bool) config('feedback.comments.confirm');

        $review = $recipe->comments()->firstOrNew(['email' => $email]);

        $review->fill([
            'name' => $request->string('name')->trim()->value(),
            'stars' => $request->integer('stars'),
            'body' => $request->string('body')->trim()->value(),
            /*
             * Back into the queue on every change, including from somebody
             * whose last one was approved. Otherwise a review is a way to
             * get words onto the page and then swap them for different ones.
             */
            'status' => $moderate ? CommentStatus::Pending : CommentStatus::Approved,
            'approved_at' => $moderate ? null : now(),
            'visitor_hash' => Visitor::identify($request),
            'ip_hash' => Visitor::addressHash($request),
        ]);

        if ($request->hasFile('photo')) {
            $this->attachPhoto($review, $request, $uploads);
        }

        /*
         * An address that has already been answered stays answered: somebody
         * rewriting their own review should not have to prove again that it
         * is their address, and the link they followed is still the reason
         * we believe it.
         */
        if (! $confirm) {
            $review->confirmed_at ??= now();
        }

        $review->save();

        // Their stars only move the figure once the review has been read.
        Ratings::recount($recipe);

        if ($confirm && $review->confirmed_at === null) {
            return $this->askThemToConfirm($review);
        }

        return back()->with('success', $moderate
            ? 'Thanks. It will show up once I have read it.'
            : 'Thanks, that is up.');
    }

    /**
     * Follows the link from the email.
     *
     * Signed rather than looked up by a token of its own, so there is nothing
     * to guess and nothing extra to store. Following it twice is not an
     * error: people forward these to themselves, and mail clients fetch
     * links on their own.
     */
    public function confirm(Request $request, Recipe $recipe, RecipeComment $review): RedirectResponse
    {
        abort_unless($review->recipe_id === $recipe->id, 404);

        $back = route('recipes.show', $recipe->slug).'#notes';

        /*
         * Checked here rather than by the signed middleware so that a link
         * somebody got round to a fortnight later says what happened and
         * what to do about it, instead of being a bare 403.
         */
        if (! $request->hasValidSignature()) {
            return redirect()->to($back)->with(
                'error',
                'That link has expired. Send the review again and I will email you a new one.',
            );
        }

        if ($review->confirmed_at === null) {
            $review->update(['confirmed_at' => now()]);

            Ratings::recount($recipe);
        }

        return redirect()->to($back)
            ->with('success', config('feedback.comments.moderate')
                ? 'Thanks, that is confirmed. I read everything before it goes up.'
                : 'Thanks, that is confirmed and up.');
    }

    /**
     * Sends the link, and says so even when sending failed.
     *
     * A mailer that is down is not something the person who just wrote a
     * review can do anything about, and telling them the review vanished
     * would be worse than telling them to look for an email that is late.
     * The failure goes in the log, where somebody can act on it.
     */
    protected function askThemToConfirm(RecipeComment $review): RedirectResponse
    {
        try {
            Mail::to($review->email)->send(new ConfirmReview($review));
        } catch (Throwable $e) {
            Log::error('Could not send a review confirmation', [
                'review' => $review->getKey(),
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Your review is saved, but the confirmation email would not send. Try again in a few minutes.');
        }

        return back()->with('success', "Nearly there. Check {$review->email} for a link to confirm it is you.");
    }

    /**
     * Their photograph, re-encoded and kept out of sight.
     *
     * Untrusted, so the bytes are written again on the way in and whatever
     * the phone put in them does not come with it. Private until the review
     * is approved, which keeps it off every page that lists photographs as
     * well as off this one.
     */
    protected function attachPhoto(
        RecipeComment $review,
        ReviewRequest $request,
        FileUploadService $uploads,
    ): void {
        $file = $uploads->store(
            $request->file('photo'),
            name: "Photo from {$request->string('name')}",
            imageType: ImageType::Photo,
            untrusted: true,
        );

        // A replaced photograph goes back out of reach rather than lingering
        // as a public URL nothing on the site points at any more.
        $review->hidePhoto();

        $review->image_id = $file->image?->id;

        $file->image?->update(['private' => true]);
    }

    /** Draft and future dated recipes take no feedback. */
    protected function isPublished(Recipe $recipe): bool
    {
        return ! $recipe->is_draft
            && $recipe->published_at !== null
            && $recipe->published_at->isPast();
    }
}
