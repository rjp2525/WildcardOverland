<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeStep extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'order', 'image_id', 'body', 'note'];

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
}
