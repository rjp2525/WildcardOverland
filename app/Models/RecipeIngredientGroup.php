<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One part of a cook: the steak, the rice, the sauce.
 *
 * Parts are shopped and prepped separately, so keeping them apart on the
 * page is the difference between a list you can work from and a wall of
 * forty bullet points.
 */
class RecipeIngredientGroup extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'order', 'name', 'note'];

    protected function casts(): array
    {
        return ['order' => 'integer', 'note' => 'array'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class, 'group_id')->orderBy('order');
    }
}
