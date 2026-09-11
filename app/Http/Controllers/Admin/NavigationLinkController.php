<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NavigationLinkRequest;
use App\Models\NavigationLink;
use App\Models\Scopes\NavigationLinkOrderedScope;
use App\Support\AdminTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class NavigationLinkController extends Controller
{
    public function index(Request $request): Response
    {
        // The model's global scope forces its own ordering, which would fight
        // the table's sort controls.
        $query = NavigationLink::query()
            ->withoutGlobalScope(NavigationLinkOrderedScope::class)
            ->with('parent:id,name');

        $table = AdminTable::for($query, $request)
            ->searchable(['name', 'route_name', 'aria_label'])
            ->sortable(['name', 'order', 'route_name', 'created_at'], 'order', 'asc');

        return Inertia::render('admin/navigation-links/Index', [
            'links' => $table->paginate()->through(fn (NavigationLink $link) => [
                'id' => $link->id,
                'name' => $link->name,
                'route_name' => $link->route_name,
                'aria_label' => $link->aria_label,
                'order' => $link->order,
                'enabled' => $link->enabled,
                'parent' => $link->parent?->only('id', 'name'),
            ]),
            'filters' => $table->state(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/navigation-links/Edit', [
            'link' => null,
            ...$this->formOptions(null),
        ]);
    }

    public function store(NavigationLinkRequest $request): RedirectResponse
    {
        $link = NavigationLink::create($request->validated());

        return redirect()
            ->route('admin.navigation-links.index')
            ->with('success', "Link \"{$link->name}\" created.");
    }

    public function edit(NavigationLink $navigationLink): Response
    {
        return Inertia::render('admin/navigation-links/Edit', [
            'link' => [
                'id' => $navigationLink->id,
                'name' => $navigationLink->name,
                'aria_label' => $navigationLink->aria_label,
                'route_name' => $navigationLink->route_name,
                'parent_id' => $navigationLink->parent_id,
                'order' => $navigationLink->order,
                'enabled' => $navigationLink->enabled,
            ],
            ...$this->formOptions($navigationLink),
        ]);
    }

    public function update(NavigationLinkRequest $request, NavigationLink $navigationLink): RedirectResponse
    {
        $navigationLink->update($request->validated());

        return redirect()
            ->route('admin.navigation-links.index')
            ->with('success', 'Link updated.');
    }

    public function destroy(NavigationLink $navigationLink): RedirectResponse
    {
        $navigationLink->delete();

        return redirect()
            ->route('admin.navigation-links.index')
            ->with('success', "Link \"{$navigationLink->name}\" deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(?NavigationLink $current): array
    {
        return [
            /*
             * Only routes a nav link can actually point at: public, reachable
             * by GET, and needing no parameters the link cannot supply.
             */
            'routeNames' => collect(Route::getRoutes()->getRoutesByName())
                ->reject(fn ($route, string $name) => str_starts_with($name, 'admin.')
                    || ! in_array('GET', $route->methods(), true)
                    || $route->parameterNames() !== [])
                ->keys()
                ->sort()
                ->values(),
            'parents' => NavigationLink::query()
                ->withoutGlobalScope(NavigationLinkOrderedScope::class)
                ->when($current, fn ($query) => $query->whereNot('id', $current->id))
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
