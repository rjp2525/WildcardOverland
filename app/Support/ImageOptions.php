<?php

namespace App\Support;

use App\Enums\ImageType;
use App\Models\Image;
use Illuminate\Support\Collection;

/**
 * The list an admin image picker chooses from.
 *
 * Three edit screens were each building this by hand, and the dropzone needs
 * to hand back exactly the same shape after an upload so the new image can
 * drop straight into the list without a page reload. One place for it.
 *
 * Unlike ImagePresenter this does not hide private images. The admin is
 * where private images are picked, so a picker that skipped them would be
 * hiding half the library from the only person allowed to see it.
 */
class ImageOptions
{
    /**
     * @param  list<ImageType>|null  $types  Restrict to these, or null for everything.
     * @param  int|null  $keep  An id to include whatever its type, so an
     *                          already-attached image never falls off its own form.
     * @return Collection<int, array<string, mixed>>
     */
    public static function list(?array $types = null, ?int $keep = null): Collection
    {
        return Image::query()
            ->with('file')
            ->when($types, fn ($query) => $query->where(fn ($q) => $q
                ->whereIn('type', $types)
                ->when($keep, fn ($q, $id) => $q->orWhere('id', $id))))
            ->orderBy('name')
            ->get()
            ->map(fn (Image $image) => static::one($image))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public static function one(Image $image): array
    {
        return [
            'value' => $image->id,
            'label' => $image->name ?: "Image #{$image->id}",
            'thumb' => static::thumb($image),
            'private' => (bool) $image->private,
        ];
    }

    /**
     * A small square to preview by. Logos and diagrams keep their own format
     * so the transparency survives; everything else goes through webp.
     */
    protected static function thumb(Image $image): ?string
    {
        if ($image->file === null) {
            return null;
        }

        $variant = ImageVariant::make(
            in_array($image->type, [ImageType::Logo, ImageType::Graphic], true) ? 'logo' : 'thumb'
        );

        // Smallest rung on the ladder. A picker tile is never bigger than
        // this, and the width has to be one the signature will accept.
        $width = min($variant->widths);

        return AssetUrl::image($image->file, $variant->name, $width);
    }
}
