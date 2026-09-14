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
        'recipe_id', 'name', 'body', 'image_id',
        'status', 'approved_at', 'visitor_hash', 'ip_hash',
    ];

    protected function casts(): array
    {
        return ['status' => CommentStatus::class, 'approved_at' => 'datetime'];
    }

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
        $query->where('status', CommentStatus::Approved);
    }
}
