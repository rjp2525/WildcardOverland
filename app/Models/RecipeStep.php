<?php

namespace App\Models;

use App\Support\RichText\TipTap;
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
        return ['order' => 'integer', 'body' => 'array'];
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

    /** The instructions as markup, from the stored document. */
    public function bodyHtml(): string
    {
        return TipTap::html($this->body);
    }
}
