<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NavigationLinkOrderedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('enabled', true)
            ->whereNull('parent_id')
            ->orderBy('order', 'DESC')
            ->with([
                'children' => fn ($q) => $q->orderBy('order', 'DESC'),
            ]);
    }
}
