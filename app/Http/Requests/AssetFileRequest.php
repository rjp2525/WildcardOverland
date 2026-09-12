<?php

namespace App\Http\Requests;

use App\Support\ImageVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetFileRequest extends FormRequest
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
            'path' => ['required', 'string'],
            'v' => ['required', 'string', Rule::in(ImageVariant::names())],
            // Checked against every variant's widths here and against this
            // variant's own in the controller: the signature is what really
            // holds the pair together, this only rejects the obvious.
            'w' => ['required', 'integer', Rule::in(ImageVariant::everyWidth())],
            's' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'v' => 'variant',
            'w' => 'width',
            's' => 'signature',
        ];
    }

    protected function prepareForValidation(): void
    {
        // The path is part of the signed payload but arrives in the route.
        $this->merge(['path' => $this->route('path')]);
    }
}
