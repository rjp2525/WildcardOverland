<?php

namespace App\Models;

use App\Enums\RatingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's stars on one recipe. Changing your mind updates it rather
 * than counting twice, which is what the unique index is for.
 */
class RecipeRating extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'stars', 'status', 'visitor_hash', 'ip_hash'];

    protected function casts(): array
    {
        return ['stars' => 'integer', 'status' => RatingStatus::class];
    }

    /**
     * The ones behind the number on the page.
     *
     * @param  Builder<RecipeRating>  $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query->where('status', RatingStatus::Counted);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
