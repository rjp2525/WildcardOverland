<?php

namespace App\Services;

use App\Enums\ImageType;
use App\Models\File;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image as LaravelImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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
            /*
             * A row on its own is not enough: it has to point at bytes that
             * are actually on the disk being served from. The two drift apart
             * - the disk changes, an object is removed - and a row without
             * its object still signs perfectly valid URLs for something that
             * is not there, which reaches the browser as a bare 404.
             */
            return $this->isReadable($existing)
                ? $existing
                : $this->restore($existing, $upload);
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
                    'dominant_color' => $this->dominantColor($upload),
                    'private' => false,
                ]);
            }

            return $file;
        });
    }

    /**
     * Whether a record's bytes can actually be read from the disk that
     * AssetController serves from.
     */
    protected function isReadable(File $file): bool
    {
        $disk = config('assets.disk');

        if ($file->disk !== $disk) {
            return false;
        }

        try {
            return Storage::disk($disk)->exists($file->stored_path);
        } catch (Throwable $e) {
            // A misconfigured disk is not a reason to lose the record.
            Log::warning('Could not check an asset on its disk', [
                'file' => $file->id,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Put a known file's bytes back on the current disk.
     *
     * The path is regenerated rather than reused: derivatives are cached
     * against the source path, so writing new bytes to the old one would
     * keep serving whatever had already been cached against it.
     */
    protected function restore(File $file, UploadedFile $upload): File
    {
        $disk = config('assets.disk');
        $storedPath = trim((string) config('assets.upload_path'), '/')
            .'/'.Str::uuid()->toString()
            .'.'.$file->original_extension;

        Storage::disk($disk)->put(
            $storedPath,
            file_get_contents($upload->getRealPath()),
            ['visibility' => 'private'],
        );

        $file->update([
            'disk' => $disk,
            'stored_path' => $storedPath,
            'size' => $upload->getSize(),
        ]);

        // A record can predate the colour column, or have been written
        // before its bytes were readable; either way this is the moment the
        // bytes are in hand again.
        if ($file->image !== null && $file->image->dominant_color === null) {
            $file->image->update(['dominant_color' => $this->dominantColor($upload)]);
        }

        return $file;
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
     * The image's average colour, for the placeholder a page paints while
     * the real bytes are in flight. Sampled once here rather than per
     * request, and never at the cost of the upload itself.
     */
    protected function dominantColor(UploadedFile $upload): ?string
    {
        try {
            return LaravelImage::fromUpload($upload)->dominantColor();
        } catch (Throwable $e) {
            Log::info('Could not sample an image colour', ['error' => $e->getMessage()]);

            return null;
        }
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
