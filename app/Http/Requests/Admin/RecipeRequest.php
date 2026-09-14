<?php

namespace App\Http\Requests\Admin;

use App\Enums\CookingMethod;
use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\SectionKind;
use App\Enums\SectionPlacement;
use App\Enums\SourceKind;
use App\Enums\TipKind;
use App\Rules\RichTextDocument;
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
            // Plain text: it is the card teaser and the meta description,
            // both of which are read as words rather than markup.
            'summary' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', new RichTextDocument],
            'method_title' => ['nullable', 'string', 'max:255'],
            'method_intro' => ['nullable', new RichTextDocument(5000)],

            'meal_type' => ['required', Rule::enum(MealType::class)],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'dietary' => ['array'],
            'dietary.*' => [Rule::enum(DietaryTag::class)],

            'prep_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'cook_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:100'],
            // Prose, because "10 to 12 big servings" is the honest answer.
            'yield' => ['nullable', 'string', 'max:255'],

            'is_draft' => ['boolean'],
            'published_at' => ['nullable', 'date'],

            /*
             * Ingredients arrive twice over: loose ones at the top level,
             * and the rest nested inside the part they belong to. Both use
             * the same shape, so the rules are shared.
             */
            'ingredients' => ['array'],
            ...$this->ingredientRules('ingredients.*'),

            'ingredient_groups' => ['array'],
            'ingredient_groups.*.name' => ['required', 'string', 'max:255'],
            'ingredient_groups.*.note' => ['nullable', new RichTextDocument(4000)],
            'ingredient_groups.*.ingredients' => ['array'],
            ...$this->ingredientRules('ingredient_groups.*.ingredients.*'),

            'steps' => ['array'],
            'steps.*.title' => ['nullable', 'string', 'max:255'],
            'steps.*.body' => ['required', new RichTextDocument],
            'steps.*.image_id' => ['nullable', 'integer', 'exists:images,id'],
            'steps.*.tips' => ['array'],
            'steps.*.tips.*.kind' => ['required', Rule::enum(TipKind::class)],
            'steps.*.tips.*.title' => ['nullable', 'string', 'max:255'],
            'steps.*.tips.*.body' => ['required', new RichTextDocument(4000)],

            'sections' => ['array'],
            'sections.*.kind' => ['required', Rule::enum(SectionKind::class)],
            'sections.*.placement' => ['required', Rule::enum(SectionPlacement::class)],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.body' => ['nullable', new RichTextDocument],

            'cooking_methods' => ['array'],
            'cooking_methods.*' => [Rule::enum(CookingMethod::class)],

            'sources' => ['array'],
            'sources.*.kind' => ['required', Rule::enum(SourceKind::class)],
            'sources.*.label' => ['required', 'string', 'max:255'],
            'sources.*.url' => ['nullable', 'url', 'max:255'],
            'sources.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * One ingredient, wherever it sits.
     *
     * @return array<string, mixed>
     */
    protected function ingredientRules(string $prefix): array
    {
        return [
            "{$prefix}.quantity" => ['nullable', 'string', 'max:64'],
            "{$prefix}.unit" => ['nullable', 'string', 'max:64'],
            "{$prefix}.item" => ['required', 'string', 'max:255'],
            "{$prefix}.note" => ['nullable', 'string', 'max:255'],
            // Free text, one sub-bullet per line.
            "{$prefix}.detail" => ['nullable', 'string', 'max:2000'],
            "{$prefix}.optional" => ['boolean'],
            "{$prefix}.in_shopping_list" => ['boolean'],
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
            'ingredient_groups.*.name' => 'part name',
            'ingredient_groups.*.ingredients.*.item' => 'ingredient',
            'steps.*.body' => 'step',
            'steps.*.tips.*.body' => 'tip',
            'sections.*.title' => 'section title',
            'sources.*.label' => 'source',
        ];
    }
}
