<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageRequest;
use App\Models\Image;
use App\Support\AdminTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImageController extends Controller
{
    public function index(Request $request): Response
    {
        $table = AdminTable::for(Image::query()->with('file'), $request)
            ->searchable(['name'])
            ->sortable(['name', 'type', 'featured', 'sort_order', 'width', 'height', 'created_at']);

        return Inertia::render('admin/images/Index', [
            'images' => $table->paginate()->through(fn (Image $image) => $this->present($image)),
            'filters' => $table->state(),
            'imageTypes' => ImageType::options(),
        ]);
    }

    public function edit(Image $image): Response
    {
        return Inertia::render('admin/images/Edit', [
            'image' => $this->present($image->load('file')),
            'imageTypes' => ImageType::options(),
        ]);
    }

    public function update(ImageRequest $request, Image $image): RedirectResponse
    {
        $image->update($request->validated());

        return redirect()
            ->route('admin.images.index')
            ->with('success', 'Image updated.');
    }

    public function destroy(Image $image): RedirectResponse
    {
        $image->delete();

        return redirect()
            ->route('admin.images.index')
            ->with('success', 'Image removed. The underlying file was kept.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Image $image): array
    {
        return [
            'id' => $image->id,
            'name' => $image->name,
            'type' => $image->type?->value,
            'type_label' => $image->type?->label(),
            'width' => $image->width,
            'height' => $image->height,
            'private' => $image->private,
            'featured' => $image->featured,
            'sort_order' => $image->sort_order,
            'caption' => $image->caption,
            'file' => $image->file ? [
                'id' => $image->file->id,
                'original_filename' => $image->file->original_filename,
                'mime' => $image->file->mime,
                'readable_size' => $image->file->readable_size,
            ] : null,
        ];
    }
}
