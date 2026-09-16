<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id', 'name', 'stars', 'email', 'confirmed_at', 'body', 'image_id',
        'status', 'approved_at', 'visitor_hash', 'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
            'status' => CommentStatus::class,
            'confirmed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Never leaves the server for a public page.
     *
     * An address is given so there is a way to reach somebody, not so it can
     * be published, and the surest way to keep it off the page is for it not
     * to be in anything the page is built from.
     *
     * @var array<int, string>
     */
    protected $hidden = ['email', 'visitor_hash', 'ip_hash'];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /** The photograph they sent, once somebody has looked at it. */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * What the public sees. Anything not yet read is not on the page.
     *
     * @param  Builder<RecipeComment>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->confirmed()->where('status', CommentStatus::Approved);
    }

    /**
     * Whoever wrote it answered the address they gave.
     *
     * Everything public goes through here. An unanswered address means
     * nobody has shown they are reachable, so the review is not in the
     * queue, not on the page and not in the average.
     *
     * @param  Builder<RecipeComment>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->whereNotNull('confirmed_at');
    }

    /**
     * Puts this review's photograph back out of reach.
     *
     * Identical uploads share one stored file, so a photograph two people
     * happened to send in is one row in the library. Hiding it because this
     * review was replaced, marked as spam or deleted would take it off the
     * other one as well, so it only goes private once nothing on the site is
     * still showing it.
     */
    public function hidePhoto(): void
    {
        if ($this->image === null) {
            return;
        }

        $shownElsewhere = static::query()
            ->where('image_id', $this->image_id)
            ->whereKeyNot($this->getKey())
            ->approved()
            ->exists();

        if (! $shownElsewhere) {
            $this->image->update(['private' => true]);
        }
    }

    /**
     * The reviews behind the published figure: read, and with stars on them.
     *
     * @param  Builder<RecipeComment>  $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query->approved()->whereNotNull('stars');
    }
}
