<?php

namespace App\Http\Requests\Feedback;

use App\Rules\NotABot;
use App\Support\Honeypot;
use Illuminate\Foundation\Http\FormRequest;

class RatingRequest extends FormRequest
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
            'stars' => ['required', 'integer', 'between:1,5'],
            Honeypot::STAMP => ['required', 'string', new NotABot],
            Honeypot::FIELD => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [Honeypot::STAMP => 'submission'];
    }
}
