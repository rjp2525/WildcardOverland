<?php

namespace App\Services;

use App\Enums\ImageType;
use App\Models\File;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Store an upload and record it, creating the matching Image row when the
     * upload is an image.
     *
     * Files are de-duplicated on their SHA-256: uploading the same bytes twice
     * returns the existing record rather than writing to the disk again.
     */
    public function store(
        UploadedFile $upload,
        string $type = 'content',
        ?string $name = null,
        ImageType $imageType = ImageType::Photo,
    ): File {
        $hash = hash_file('sha256', $upload->getRealPath());

        if ($existing = File::where('hash', $hash)->first()) {
            return $existing;
        }

        $disk = config('assets.disk');
        $extension = strtolower($upload->getClientOriginalExtension() ?: $upload->guessExtension() ?: 'bin');
        $storedPath = trim((string) config('assets.upload_path'), '/').'/'.Str::uuid()->toString().'.'.$extension;

        Storage::disk($disk)->put(
            $storedPath,
            file_get_contents($upload->getRealPath()),
            ['visibility' => 'private'],
        );

        return DB::transaction(function () use ($upload, $hash, $disk, $extension, $storedPath, $type, $name, $imageType): File {
            $file = File::create([
                'name' => $name ?: pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $upload->getClientOriginalName(),
                'original_extension' => $extension,
                'mime' => $upload->getClientMimeType(),
                'hash' => $hash,
                'type' => $type,
                'size' => $upload->getSize(),
                'stored_path' => $storedPath,
                'disk' => $disk,
            ]);

            if ($file->isImage()) {
                [$width, $height] = $this->dimensions($upload);

                Image::create([
                    'name' => $file->name,
                    'type' => $imageType,
                    'file_id' => $file->id,
                    'width' => $width,
                    'height' => $height,
                    'private' => false,
                ]);
            }

            return $file;
        });
    }

    public function delete(File $file): void
    {
        DB::transaction(function () use ($file): void {
            $file->image?->delete();
            $file->deleteFromDisk();
            $file->forceDelete();
        });
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    protected function dimensions(UploadedFile $upload): array
    {
        $size = @getimagesize($upload->getRealPath());

        return $size === false
            ? [null, null]
            : [$size[0] ?? null, $size[1] ?? null];
    }
}
