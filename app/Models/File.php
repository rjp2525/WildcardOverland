<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'original_filename',
        'original_extension',
        'mime',
        'hash',
        'type',
        'size',
        'stored_path',
        'disk',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function image(): HasOne
    {
        return $this->hasOne(Image::class, 'file_id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    /**
     * Human readable size, e.g. "1.4 MB".
     */
    public function getReadableSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    }

    public function deleteFromDisk(): void
    {
        Storage::disk($this->disk)->delete($this->stored_path);
    }
}
