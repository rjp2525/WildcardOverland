<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileUploadService;
use App\Support\AdminTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FileController extends Controller
{
    public function index(Request $request): Response
    {
        $table = AdminTable::for(File::query()->with('image'), $request)
            ->searchable(['name', 'original_filename', 'mime'])
            ->sortable(['name', 'size', 'mime', 'type', 'created_at']);

        return Inertia::render('admin/files/Index', [
            'files' => $table->paginate()->through(fn (File $file) => [
                'id' => $file->id,
                'name' => $file->name,
                'original_filename' => $file->original_filename,
                'mime' => $file->mime,
                'type' => $file->type,
                'size' => $file->size,
                'readable_size' => $file->readable_size,
                'is_image' => $file->isImage(),
                'image_id' => $file->image?->id,
                'created_at' => $file->created_at?->toDateTimeString(),
            ]),
            'filters' => $table->state(),
            'maxUploadKb' => config('assets.max_upload_kb'),
        ]);
    }

    public function store(Request $request, FileUploadService $uploads): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.config('assets.max_upload_kb')],
            'type' => ['nullable', 'string', 'in:content,static'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $uploads->store(
            $request->file('file'),
            $validated['type'] ?? 'content',
            $validated['name'] ?? null,
        );

        return back()->with('success', "Uploaded \"{$file->name}\".");
    }

    public function destroy(File $file, FileUploadService $uploads): RedirectResponse
    {
        $name = $file->name;

        $uploads->delete($file);

        return redirect()
            ->route('admin.files.index')
            ->with('success', "Deleted \"{$name}\".");
    }
}
