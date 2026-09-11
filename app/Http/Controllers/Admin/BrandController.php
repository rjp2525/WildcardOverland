<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use App\Models\Image;
use App\Support\AdminTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $table = AdminTable::for(Brand::query(), $request)
            ->searchable(['name', 'website', 'description'])
            ->sortable(['name', 'created_at'], 'name', 'asc');

        return Inertia::render('admin/brands/Index', [
            'brands' => $table->paginate()->through(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'website' => $brand->website,
                'primary_color' => $brand->primary_color,
                'secondary_color' => $brand->secondary_color,
                'logo' => $brand->logo?->only('id', 'name'),
            ]),
            'filters' => $table->state(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/brands/Edit', [
            'brand' => null,
            'images' => $this->imageOptions(),
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = Brand::create($request->validated());

        return redirect()
            ->route('admin.brands.index')
            ->with('success', "Brand \"{$brand->name}\" created.");
    }

    public function edit(Brand $brand): Response
    {
        return Inertia::render('admin/brands/Edit', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo_image_id' => $brand->logo_image_id,
                'website' => $brand->website,
                'description' => $brand->description,
                'primary_color' => $brand->primary_color,
                'secondary_color' => $brand->secondary_color,
                'notes' => $brand->notes,
            ],
            'images' => $this->imageOptions(),
        ]);
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()
            ->route('admin.brands.index')
            ->with('success', "Brand \"{$brand->name}\" deleted.");
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function imageOptions()
    {
        return Image::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Image $image) => [
                'value' => $image->id,
                'label' => $image->name ?: "Image #{$image->id}",
            ]);
    }
}
