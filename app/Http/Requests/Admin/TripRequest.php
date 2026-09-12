<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TripRequest extends FormRequest
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
        $tripId = $this->route('trip')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('trips', 'slug')->ignore($tripId),
            ],
            'headline' => ['nullable', 'string', 'max:255'],
            'hero_image_id' => ['nullable', 'integer', 'exists:images,id'],
            'images' => ['array'],
            'images.*.id' => ['required', 'integer', 'exists:images,id'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'recipes' => ['array'],
            'recipes.*.id' => ['required', 'integer', 'exists:recipes,id'],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'miles' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_draft' => ['boolean'],
            'published_at' => ['nullable', 'date'],

            'campsites' => ['array'],
            'campsites.*.id' => ['nullable', 'integer', 'exists:campsites,id'],
            'campsites.*.name' => ['required', 'string', 'max:255'],
            'campsites.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'campsites.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'campsites.*.nights' => ['nullable', 'integer', 'min:0'],
            'campsites.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Derive the slug from the name when one isn't supplied.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('name')),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'is_draft' => 'draft',
            'campsites.*.name' => 'campsite name',
            'campsites.*.latitude' => 'latitude',
            'campsites.*.longitude' => 'longitude',
            'campsites.*.nights' => 'nights',
        ];
    }
}
