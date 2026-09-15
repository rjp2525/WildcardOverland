<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Enums\ImageType;
use App\Http\Requests\Feedback\ReviewRequest;
use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Services\FileUploadService;
use App\Support\Ratings;
use App\Support\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

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

        $review->save();

        // Their stars only move the figure once the review has been read.
        Ratings::recount($recipe);

        return back()->with('success', $moderate
            ? 'Thanks. It will show up once I have read it.'
            : 'Thanks, that is up.');
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
