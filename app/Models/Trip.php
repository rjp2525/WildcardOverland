<?php

namespace App\Models;

use App\Observers\TripObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TripObserver::class)]
class Trip extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'headline',
        'hero_image_id',
        'summary',
        'content',
        'start_date',
        'end_date',
        'is_draft',
        'published_at',
    ];

    /**
     * `calculated_nights` is deliberately not fillable - TripObserver derives
     * it from the start and end dates.
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'published_at' => 'datetime',
            'is_draft' => 'boolean',
        ];
    }

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'hero_image_id');
    }

    /**
     * Ordered gallery. `order` and `caption` live on the pivot so the same
     * image can appear in several trips with different captions.
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Image::class)
            ->withPivot(['order', 'caption'])
            ->withTimestamps()
            ->orderBy('image_trip.order');
    }

    public function campsites(): HasMany
    {
        return $this->hasMany(Campsite::class)->orderBy('order');
    }

    public function isPublished(): bool
    {
        return ! $this->is_draft
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * The query-side counterpart of isPublished(): live on the public site.
     *
     * @param  Builder<Trip>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
