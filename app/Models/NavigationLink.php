<?php

namespace App\Models;

use App\Models\Scopes\NavigationLinkOrderedScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([NavigationLinkOrderedScope::class])]
class NavigationLink extends Model
{
    protected $fillable = [
        'parent_id',
        'order',
        'name',
        'aria_label',
        'route_name',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
