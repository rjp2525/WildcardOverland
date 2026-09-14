<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipeStep extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'order', 'title', 'image_id', 'body'];

    protected function casts(): array
    {
        return ['order' => 'integer'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /** What the pan should look like at this point. */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public function tips(): HasMany
    {
        return $this->hasMany(RecipeStepTip::class)->orderBy('order');
    }

    /**
     * The step's own instructions, split into paragraphs.
     *
     * Real steps are several beats long. Rendering the lot as one block
     * turns "sear, wait, flip, push aside" into a paragraph nobody can
     * follow with a spatula in one hand.
     *
     * @return array<int, string>
     */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map(
            trim(...),
            preg_split('/\R{2,}/', (string) $this->body) ?: [],
        ), fn (string $part) => $part !== ''));
    }
}
