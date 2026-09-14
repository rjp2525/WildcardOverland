<?php

namespace App\Models;

use App\Enums\TipKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An aside belonging to one step rather than to the recipe.
 *
 * These are the things you only learn by getting them wrong: do not walk
 * away from the garlic, stop stirring the rice, sesame oil goes in late.
 */
class RecipeStepTip extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_step_id', 'order', 'kind', 'title', 'body'];

    protected function casts(): array
    {
        return ['order' => 'integer', 'body' => 'array', 'kind' => TipKind::class];
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(RecipeStep::class, 'recipe_step_id');
    }
}
