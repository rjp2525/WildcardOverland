<?php

namespace App\Models;

use App\Enums\SectionKind;
use App\Enums\SectionPlacement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A titled block of a recipe that is neither an ingredient nor a step:
 * prep done at home, a technique worth its own heading, packing lists,
 * what to change when you are feeding eighteen people.
 */
class RecipeSection extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'order', 'kind', 'placement', 'title', 'body'];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'body' => 'array',
            'kind' => SectionKind::class,
            'placement' => SectionPlacement::class,
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
