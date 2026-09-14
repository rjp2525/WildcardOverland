<?php

namespace App\Http\Requests\Feedback;

use App\Rules\NotABot;
use App\Support\Honeypot;
use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'min:2', 'max:'.config('feedback.comments.max_length')],
            Honeypot::STAMP => ['required', 'string', new NotABot('comment')],
            Honeypot::FIELD => ['nullable'],
        ];

        if (config('feedback.comments.photos')) {
            $rules['photo'] = [
                'nullable', 'file',
                // Photographs only. No SVG here: it is a document, and this
                // is the one upload route a stranger can reach.
                'mimes:jpg,jpeg,png,webp,heic,heif',
                'max:'.config('feedback.comments.photo_max_kb'),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [Honeypot::STAMP => 'submission', 'body' => 'comment'];
    }
}
