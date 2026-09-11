<?php

namespace App\Http\Requests;

use App\Enums\ImageEnum;
use Illuminate\Foundation\Http\FormRequest;

class AssetFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /**
         * VALUE DEFAULTS IF NO VALUE SPECIFIED
         * fit = max, q = 80
         *
         * t => type - si (static image), i (image), f (file)
         * w => width (required when type is si or i, allowed values are from ImageEnum::ALLOWED_WIDTHS)
         * h => height (required when type is si or i, allowed values are from ImageEnum::ALLOWED_HEIGHTS)
         * fm => format (required when type is i, allowed values are from ImageEnum::ALLOWED_TYPES)
         * q => quality (required when type is i, allowed values are from ImageEnum::ALLOWED_QUALITIES)
         * fit => fitment (required when type is i, allowed values are from ImageEnum::ALLOWED_FITMENTS)
         */
        return [
            'path' => 'required|string',
            't' => 'required|string|in:si,i,f',
            'w' => 'nullable|integer|required_if:t,si|required_if:t,i|in:' . implode(',', ImageEnum::ALLOWED_WIDTHS),
            'h' => 'nullable|integer|required_if:t,si|required_if:t,i|in:' . implode(',', ImageEnum::ALLOWED_HEIGHTS),
            'fm' => 'nullable|string|required_if:t,i|in:' . implode(',', ImageEnum::ALLOWED_TYPES),
            'q' => 'nullable|integer|required_if:t,i|in:' . implode(',', ImageEnum::ALLOWED_QUALITIES),
            'fit' => 'nullable|string|required_if:t,i|in:' . implode(',', ImageEnum::ALLOWED_FITMENTS),
        ];
    }

    public function attributes(): array
    {
        return [
            't' => 'type',
            'w' => 'width',
            'h' => 'height',
            'fm' => 'format',
            'q' => 'quality',
            'fit' => 'fitment',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'path' => $this->route('path'),
            'q' => $this->input('q', ImageEnum::DEFAULT_QUALITY),
            'fit' => $this->input('fit', ImageEnum::DEFAULT_FITMENT),
        ]);
    }
}
