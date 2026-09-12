<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'order', 'quantity', 'unit', 'item', 'note'];

    protected function casts(): array
    {
        return ['order' => 'integer'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
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
