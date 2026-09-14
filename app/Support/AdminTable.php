<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Applies the search / sort / paginate query string conventions shared by
 * every admin index screen, so the Vue DataTable can talk to any resource
 * the same way.
 */
class AdminTable
{
    /** @var array<int, string> */
    protected array $searchable = [];

    /** @var array<int, string> */
    protected array $sortable = [];

    protected string $defaultSort = 'created_at';

    protected string $defaultDirection = 'desc';

    protected int $perPage = 15;

    protected bool $trashable = false;

    public function __construct(
        protected Builder $query,
        protected Request $request,
    ) {}

    public static function for(Builder $query, Request $request): self
    {
        return new self($query, $request);
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function searchable(array $columns): self
    {
        $this->searchable = $columns;

        return $this;
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function sortable(array $columns, string $default = 'created_at', string $direction = 'desc'): self
    {
        $this->sortable = $columns;
        $this->defaultSort = $default;
        $this->defaultDirection = $direction;

        return $this;
    }

    /**
     * Lets the screen switch between live rows and the trash.
     *
     * Without this a soft deleted record is simply gone from the admin,
     * which is the worst of both worlds: still in the database, taking up
     * its own slug, and impossible to restore or finish deleting.
     */
    public function trashable(): self
    {
        $this->trashable = true;

        return $this;
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function paginate(): LengthAwarePaginator
    {
        $this->applyTrashed();
        $this->applySearch();
        $this->applySort();

        return $this->query
            ->paginate($this->perPage)
            ->withQueryString();
    }

    /**
     * The current table state, echoed back so the UI can reflect it.
     *
     * @return array<string, mixed>
     */
    public function state(): array
    {
        return [
            'search' => $this->searchTerm(),
            'sort' => $this->sortColumn(),
            'direction' => $this->sortDirection(),
            'trashed' => $this->trashable ? $this->trashedFilter() : null,
        ];
    }

    protected function applyTrashed(): void
    {
        if (! $this->trashable) {
            return;
        }

        match ($this->trashedFilter()) {
            'only' => $this->query->onlyTrashed(),
            'with' => $this->query->withTrashed(),
            default => null,
        };
    }

    /** One of "none", "with" or "only". Anything else means "none". */
    protected function trashedFilter(): string
    {
        $value = (string) $this->request->query('trashed', 'none');

        return in_array($value, ['none', 'with', 'only'], true) ? $value : 'none';
    }

    protected function applySearch(): void
    {
        $term = $this->searchTerm();

        if ($term === null || $this->searchable === []) {
            return;
        }

        $this->query->where(function (Builder $query) use ($term): void {
            foreach ($this->searchable as $column) {
                $query->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    protected function applySort(): void
    {
        $this->query->orderBy($this->sortColumn(), $this->sortDirection());
    }

    protected function searchTerm(): ?string
    {
        $term = trim((string) $this->request->query('search', ''));

        return $term === '' ? null : $term;
    }

    /**
     * Only whitelisted columns are accepted, so the query string can't be
     * used to order by arbitrary SQL.
     */
    protected function sortColumn(): string
    {
        $sort = (string) $this->request->query('sort', $this->defaultSort);

        return in_array($sort, $this->sortable, true) ? $sort : $this->defaultSort;
    }

    protected function sortDirection(): string
    {
        return strtolower((string) $this->request->query('direction', $this->defaultDirection)) === 'asc'
            ? 'asc'
            : 'desc';
    }
}
