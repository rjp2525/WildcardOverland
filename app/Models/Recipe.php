<?php

namespace App\Models;

use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'headline',
        'hero_image_id',
        'summary',
        'notes',
        'meal_type',
        'difficulty',
        'dietary',
        'prep_minutes',
        'cook_minutes',
        'servings',
        'is_draft',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'meal_type' => MealType::class,
            'difficulty' => Difficulty::class,
            'dietary' => 'array',
            'prep_minutes' => 'integer',
            'cook_minutes' => 'integer',
            'servings' => 'integer',
            'is_draft' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'hero_image_id');
    }

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class)->withTimestamps();
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('order');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('order');
    }

    /** Where the recipe came from, and what it started as. */
    public function sources(): HasMany
    {
        return $this->hasMany(RecipeSource::class)->orderBy('order');
    }

    /**
     * Mirrors Trip::scopePublished() - live on the public site.
     *
     * @param  Builder<Recipe>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function totalMinutes(): ?int
    {
        if ($this->prep_minutes === null && $this->cook_minutes === null) {
            return null;
        }

        return (int) $this->prep_minutes + (int) $this->cook_minutes;
    }

    /**
     * @return array<int, DietaryTag>
     */
    public function dietaryTags(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value) => DietaryTag::tryFrom($value),
            $this->dietary ?? [],
        )));
    }
}
