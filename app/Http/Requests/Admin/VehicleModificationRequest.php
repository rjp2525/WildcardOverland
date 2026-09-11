<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VehicleModificationRequest extends FormRequest
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
            'vendor' => ['nullable', 'string', 'max:255'],
            'purchased_from' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'install_date' => ['nullable', 'date'],
            // Submitted in dollars; stored as integer cents.
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'url' => ['nullable', 'url', 'max:255'],
            'shown_on_timeline' => ['boolean'],
        ];
    }
}
