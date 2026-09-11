<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class NavigationLinkRequest extends FormRequest
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
        $id = $this->route('navigation_link')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'aria_label' => ['nullable', 'string', 'max:255'],
            'route_name' => [
                'required', 'string',
                Rule::in(array_keys(Route::getRoutes()->getRoutesByName())),
            ],
            // A link may not be its own parent.
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('navigation_links', 'id')->when(
                    (bool) $id,
                    fn ($rule) => $rule->whereNot('id', $id),
                ),
            ],
            'order' => ['integer', 'min:0'],
            'enabled' => ['boolean'],
        ];
    }
}
