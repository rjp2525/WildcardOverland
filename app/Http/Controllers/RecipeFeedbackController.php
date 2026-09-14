<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Enums\ImageType;
use App\Http\Requests\Feedback\CommentRequest;
use App\Http\Requests\Feedback\RatingRequest;
use App\Models\Recipe;
use App\Services\FileUploadService;
use App\Support\Visitor;
use Illuminate\Http\RedirectResponse;

/**
 * What people who cooked it send back.
 *
 * Nobody signs in, so everything here has to survive being open to the
 * whole internet. Three things do that work: a honeypot on the form, a
 * throttle on the route, and a queue that anything written by a stranger
 * sits in until somebody has read it.
 */
class RecipeFeedbackController extends Controller
{
    /**
     * Stars, one set per person per recipe.
     *
     * Changing your mind updates what is there. A rating is a number with no
     * words in it, so unlike a comment there is nothing to moderate: the
     * defence is that one visitor only ever counts once.
     */
    public function rate(RatingRequest $request, Recipe $recipe): RedirectResponse
    {
        abort_unless($this->isPublished($recipe), 404);

        $recipe->ratings()->updateOrCreate(
            ['visitor_hash' => Visitor::identify($request)],
            [
                'stars' => $request->integer('stars'),
                'ip_hash' => Visitor::addressHash($request),
            ],
        );

        return back()->with('success', 'Thanks, that is noted.');
    }

    /**
     * A comment, and a photograph with it if they sent one.
     */
    public function comment(
        CommentRequest $request,
        Recipe $recipe,
        FileUploadService $uploads,
    ): RedirectResponse {
        abort_unless($this->isPublished($recipe), 404);

        $image = null;

        if ($request->hasFile('photo')) {
            /*
             * Untrusted, so the bytes are re-encoded on the way in and
             * whatever the phone wrote into them does not come with it.
             * Private until approved, which keeps it off every page that
             * lists photographs as well as off this one.
             */
            $file = $uploads->store(
                $request->file('photo'),
                name: "Photo from {$request->string('name')}",
                imageType: ImageType::Photo,
                untrusted: true,
            );

            $image = $file->image;
            $image?->update(['private' => true]);
        }

        $moderate = (bool) config('feedback.comments.moderate');

        $recipe->comments()->create([
            'name' => $request->string('name')->trim()->value(),
            'body' => $request->string('body')->trim()->value(),
            'image_id' => $image?->id,
            'status' => $moderate ? CommentStatus::Pending : CommentStatus::Approved,
            'approved_at' => $moderate ? null : now(),
            'visitor_hash' => Visitor::identify($request),
            'ip_hash' => Visitor::addressHash($request),
        ]);

        return back()->with('success', $moderate
            ? 'Thanks. It will show up once I have read it.'
            : 'Thanks, that is up.');
    }

    /** Draft and future dated recipes take no feedback. */
    protected function isPublished(Recipe $recipe): bool
    {
        return ! $recipe->is_draft
            && $recipe->published_at !== null
            && $recipe->published_at->isPast();
    }
}
