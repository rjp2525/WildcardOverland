<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Models\RecipeComment;
use App\Support\AdminTable;
use App\Support\ImagePresenter;
use App\Support\Ratings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The queue everything a stranger wrote sits in.
 *
 * Approving is the only thing that puts a comment on the page, and the only
 * thing that makes a photograph public. Spam is marked rather than deleted,
 * so the same sender turning up again is visible rather than forgotten.
 */
class CommentController extends Controller
{
    /**
     * @return array<int, string>
     */
    protected static function tabs(): array
    {
        return [...array_column(CommentStatus::cases(), 'value'), 'unconfirmed'];
    }

    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: CommentStatus::Pending->value;

        if (! in_array($status, static::tabs(), true)) {
            $status = CommentStatus::Pending->value;
        }

        /*
         * Unconfirmed is not a status, it is the absence of somebody having
         * answered their address. It gets a tab of its own because it is a
         * different question: these are not waiting on a decision, they are
         * waiting on the person who wrote them.
         */
        $query = RecipeComment::query()->with(['recipe:id,name,slug', 'image.file']);

        $status === 'unconfirmed'
            ? $query->whereNull('confirmed_at')
            : $query->confirmed()->where('status', $status);

        $table = AdminTable::for($query, $request)
            ->searchable(['name', 'body'])
            ->sortable(['name', 'created_at'], 'created_at');

        return Inertia::render('admin/comments/Index', [
            'comments' => $table->paginate()->through(fn (RecipeComment $comment) => [
                'id' => $comment->id,
                'name' => $comment->name,
                // Only ever on this screen. It is how to reach them, not
                // something anybody else gets to see.
                'email' => $comment->email,
                'stars' => $comment->stars,
                'body' => $comment->body,
                'status' => $comment->status->value,
                'posted' => $comment->created_at?->toDayDateTimeString(),
                'recipe' => $comment->recipe?->name,
                'recipeUrl' => $comment->recipe
                    ? route('recipes.show', $comment->recipe->slug)
                    : null,
                'photo' => ImagePresenter::step($comment->image, "Photo from {$comment->name}"),
                'confirmed' => $comment->confirmed_at !== null,
                /*
                 * The same sender's other submissions. Two is a person who
                 * liked two recipes; twenty in an hour is not.
                 */
                'alsoFrom' => $comment->ip_hash === null ? 0 : RecipeComment::query()
                    ->where('ip_hash', $comment->ip_hash)
                    ->whereKeyNot($comment->id)
                    ->count(),
            ]),
            'filters' => $table->state(),
            'status' => $status,
            'statuses' => [
                ...CommentStatus::options(),
                ['value' => 'unconfirmed', 'label' => 'Unconfirmed'],
            ],
            'counts' => [
                ...RecipeComment::query()
                    ->whereNotNull('confirmed_at')
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->all(),
                'unconfirmed' => RecipeComment::query()->whereNull('confirmed_at')->count(),
            ],
        ]);
    }

    /**
     * Puts a comment on the page, and its photograph with it.
     *
     * The photograph is uploaded private and stays that way until this
     * moment, so nothing anybody sent in is reachable before it is read.
     */
    public function update(Request $request, RecipeComment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(CommentStatus::class)],
        ]);

        $status = CommentStatus::from($validated['status']);

        $comment->update([
            'status' => $status,
            'approved_at' => $status === CommentStatus::Approved ? now() : null,
            /*
             * Putting something on the page by hand vouches for it, so an
             * unanswered address stops being a reason to hold it. Without
             * this, approving an unconfirmed review would file it somewhere
             * nothing reads from and it would simply vanish.
             */
            'confirmed_at' => $status === CommentStatus::Approved
                ? ($comment->confirmed_at ?? now())
                : $comment->confirmed_at,
        ]);

        if ($status === CommentStatus::Approved) {
            $comment->image?->update(['private' => false]);
        } else {
            $comment->hidePhoto();
        }

        /*
         * Their stars go on or come off the published figure with their
         * words, because approving a review is the only thing that puts
         * either of them on the page.
         */
        $comment->loadMissing('recipe');

        if ($comment->recipe !== null) {
            Ratings::recount($comment->recipe);
        }

        return back()->with('success', match ($status) {
            CommentStatus::Approved => "\"{$comment->name}\" is on the page.",
            CommentStatus::Spam => 'Marked as spam.',
            CommentStatus::Pending => 'Put back in the queue.',
        });
    }

    public function destroy(RecipeComment $comment): RedirectResponse
    {
        $recipe = $comment->recipe;

        $comment->hidePhoto();
        $comment->delete();

        if ($recipe !== null) {
            Ratings::recount($recipe);
        }

        return back()->with('success', 'Deleted for good.');
    }
}
