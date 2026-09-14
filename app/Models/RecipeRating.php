<?php

namespace App\Models;

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

    protected $fillable = ['recipe_id', 'stars', 'visitor_hash', 'ip_hash'];

    protected function casts(): array
    {
        return ['stars' => 'integer'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
