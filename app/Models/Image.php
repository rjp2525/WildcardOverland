<?php

namespace App\Models;

use App\Enums\ImageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'file_id',
        'width',
        'height',
        'private',
    ];

    protected function casts(): array
    {
        return [
            'type' => ImageType::class,
            'width' => 'integer',
            'height' => 'integer',
            'private' => 'boolean',
        ];
    }

    /**
     * Photographs that are publicly visible - what the About page counts.
     *
     * @param  Builder<Image>  $query
     */
    public function scopePublicPhotos(Builder $query): void
    {
        $query->where('type', ImageType::Photo)->where('private', false);
    }

    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class, 'logo_image_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}
