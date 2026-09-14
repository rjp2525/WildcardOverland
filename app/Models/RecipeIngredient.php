<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id', 'group_id', 'order', 'quantity', 'unit', 'item',
        'note', 'detail', 'optional', 'in_shopping_list',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'optional' => 'boolean',
            'in_shopping_list' => 'boolean',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /** The part of the cook this belongs to, if it belongs to one. */
    public function group(): BelongsTo
    {
        return $this->belongsTo(RecipeIngredientGroup::class, 'group_id');
    }

    /**
     * The sub-bullets under an ingredient, one per line.
     *
     * "Sirloin is the best balance of flavour and price" is guidance about
     * what to buy, not a second ingredient, so it hangs off this one.
     *
     * @return array<int, string>
     */
    public function details(): array
    {
        return array_values(array_filter(array_map(
            trim(...),
            preg_split('/\R/', (string) $this->detail) ?: [],
        ), fn (string $line) => $line !== ''));
    }

    /**
     * "1 1/2 cups oats" from its parts, skipping whatever is missing.
     */
    public function label(): string
    {
        return trim(implode(' ', array_filter([
            $this->quantity,
            $this->unit,
            $this->item,
        ])));
    }
}
