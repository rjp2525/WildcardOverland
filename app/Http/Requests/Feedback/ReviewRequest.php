<?php

namespace App\Http\Requests\Feedback;

use App\Rules\NotABot;
use App\Support\Honeypot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ReviewRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:60'],

            /*
             * Not published, and not optional. Stars used to be one tap from
             * anybody, which is what made them worth manufacturing. Asking
             * for a real address is most of what makes that not worth doing.
             */
            'email' => ['required', 'string', 'email:filter', 'max:255'],

            'stars' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:2', 'max:'.config('feedback.comments.max_length')],
            Honeypot::STAMP => ['required', 'string', new NotABot('review')],
            Honeypot::FIELD => ['nullable'],
        ];

        if (config('feedback.comments.photos')) {
            $rules['photo'] = [
                'nullable',
                // Photographs only. No SVG here: it is a document, and this
                // is the one upload route a stranger can reach.
                File::types(['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'])
                    ->max(config('feedback.comments.photo_max_kb').'kb'),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [Honeypot::STAMP => 'submission', 'body' => 'review', 'stars' => 'rating'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stars.required' => 'Give it a rating out of five as well.',
            'email.required' => 'An email address, so there is a way to reach you. It is never shown.',
        ];
    }
}
