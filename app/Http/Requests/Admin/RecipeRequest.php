<?php

namespace App\Http\Requests\Admin;

use App\Enums\CookingMethod;
use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\SourceKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $recipeId = $this->route('recipe')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('recipes', 'slug')->ignore($recipeId),
            ],
            'headline' => ['nullable', 'string', 'max:255'],
            'hero_image_id' => ['nullable', 'integer', 'exists:images,id'],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'meal_type' => ['required', Rule::enum(MealType::class)],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'dietary' => ['array'],
            'dietary.*' => [Rule::enum(DietaryTag::class)],

            'prep_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'cook_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:100'],

            'is_draft' => ['boolean'],
            'published_at' => ['nullable', 'date'],

            'ingredients' => ['array'],
            'ingredients.*.quantity' => ['nullable', 'string', 'max:64'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:64'],
            'ingredients.*.item' => ['required', 'string', 'max:255'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
            'ingredients.*.in_shopping_list' => ['boolean'],

            'steps' => ['array'],
            'steps.*.body' => ['required', 'string'],
            'steps.*.note' => ['nullable', 'string', 'max:1000'],
            'steps.*.image_id' => ['nullable', 'integer', 'exists:images,id'],

            'cooking_methods' => ['array'],
            'cooking_methods.*' => [Rule::enum(CookingMethod::class)],

            'sources' => ['array'],
            'sources.*.kind' => ['required', Rule::enum(SourceKind::class)],
            'sources.*.label' => ['required', 'string', 'max:255'],
            'sources.*.url' => ['nullable', 'url', 'max:255'],
            'sources.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'is_draft' => 'draft',
            'meal_type' => 'meal type',
            'hero_image_id' => 'hero image',
            'ingredients.*.item' => 'ingredient',
            'steps.*.body' => 'step',
            'sources.*.label' => 'source',
        ];
    }
}
