<?php

namespace App\Models;

use App\Enums\CookingMethod;
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
        'method_intro',
        'method_title',
        'meal_type',
        'difficulty',
        'dietary',
        'cooking_methods',
        'prep_minutes',
        'cook_minutes',
        'servings',
        'yield',
        'is_draft',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'array',
            'method_intro' => 'array',
            'meal_type' => MealType::class,
            'difficulty' => Difficulty::class,
            'dietary' => 'array',
            'cooking_methods' => 'array',
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

    /** The parts of the cook, each with its own ingredients. */
    public function ingredientGroups(): HasMany
    {
        return $this->hasMany(RecipeIngredientGroup::class)->orderBy('order');
    }

    /** Prep, technique, packing, scaling. Everything that is not a step. */
    public function sections(): HasMany
    {
        return $this->hasMany(RecipeSection::class)->orderBy('order');
    }

    /**
     * Ingredients that belong to no part, which is what a short recipe has
     * and what a long one has left over at the top.
     */
    public function looseIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->whereNull('group_id')->orderBy('order');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RecipeRating::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RecipeComment::class)->latest();
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

    /**
     * The stars a card needs, counted in the listing query.
     *
     * A card shows an average, and working one out per card is a query per
     * card. Both aggregates come back on the row instead.
     *
     * @param  Builder<Recipe>  $query
     */
    public function scopeWithRatingSummary(Builder $query): void
    {
        $query->withCount('ratings')->withAvg('ratings', 'stars');
    }

    public function totalMinutes(): ?int
    {
        if ($this->prep_minutes === null && $this->cook_minutes === null) {
            return null;
        }

        return (int) $this->prep_minutes + (int) $this->cook_minutes;
    }

    /**
     * @return array<int, CookingMethod>
     */
    public function cookingMethods(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value) => CookingMethod::tryFrom($value),
            $this->cooking_methods ?? [],
        )));
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
