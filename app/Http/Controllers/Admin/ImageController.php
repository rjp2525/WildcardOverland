<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageRequest;
use App\Models\Image;
use App\Services\FileUploadService;
use App\Support\AdminTable;
use App\Support\ImageOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    /**
     * Uploads one image and hands the new row straight back as JSON.
     *
     * The rest of the admin posts through Inertia and gets a redirect, which
     * is right for a form. A dropzone sitting inside another form cannot
     * navigate away, and needs the id of what it just made so it can select
     * it, so this one answers in JSON.
     */
    public function upload(Request $request, FileUploadService $uploads): JsonResponse
    {
        $validated = $request->validate([
            /*
             * Laravel's `image` rule turns SVG away, which is the wrong
             * answer for a logo. They are accepted here and sanitised on the
             * way to disk instead. Listing the types by hand also means the
             * error names them.
             */
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,avif,svg', 'max:'.config('assets.max_upload_kb')],
            'name' => ['nullable', 'string', 'max:255'],
            'image_type' => ['nullable', Rule::enum(ImageType::class)],
        ]);

        $file = $uploads->store(
            $request->file('file'),
            type: 'content',
            name: $validated['name'] ?? null,
            imageType: isset($validated['image_type'])
                ? ImageType::from($validated['image_type'])
                : ImageType::Photo,
        );

        $image = $file->image;

        abort_if($image === null, 422, 'That file is not an image.');

        return response()->json(ImageOptions::one($image));
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
