<?php

namespace App\Models;

use App\Enums\SourceKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeSource extends Model
{
    protected $fillable = ['recipe_id', 'order', 'kind', 'label', 'url', 'note'];

    protected function casts(): array
    {
        return [
            'kind' => SourceKind::class,
            'order' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
