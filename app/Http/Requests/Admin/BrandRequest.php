<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo_image_id' => ['nullable', 'integer', 'exists:images,id'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'max:32', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'secondary_color' => ['nullable', 'string', 'max:32', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'primary_color.regex' => 'The primary color must be a hex value, such as #e85a2f.',
            'secondary_color.regex' => 'The secondary color must be a hex value, such as #e85a2f.',
        ];
    }
}
