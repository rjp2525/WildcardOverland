<script setup lang="ts" generic="T extends Record<string, any>">
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowDown, ArrowUp, ChevronsUpDown, Search } from 'lucide-vue-next'
import Input from './Input.vue'
import { cn } from '@/lib/utils'
import { useRoute } from '@/lib/route'
import type { Column, Paginated } from './table'

const route = useRoute()

const props = defineProps<{
  columns: Column[]
  rows: Paginated<T>
  filters: { search: string | null; sort: string; direction: string; trashed?: string | null }
  /** Route to reload when search/sort change. */
  routeName: string
  searchPlaceholder?: string
  emptyMessage?: string
  /** Adds the live / trash switch. Only for resources that soft delete. */
  trashable?: boolean
  /** How many are sitting in the trash, for the tab label. */
  trashedCount?: number
}>()

const search = ref(props.filters.search ?? '')
let debounce: ReturnType<typeof setTimeout> | undefined

const trashed = computed(() => props.filters.trashed ?? 'none')

/** Every navigation carries the whole filter set, so none of it is lost. */
function reload(overrides: Record<string, string | undefined>) {
  router.get(
    route(props.routeName),
    {
      search: search.value || undefined,
      sort: props.filters.sort,
      direction: props.filters.direction,
      trashed: trashed.value === 'none' ? undefined : trashed.value,
      ...overrides,
    },
    { preserveState: true, replace: true },
  )
}

watch(search, () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => reload({}), 300)
})

function toggleSort(column: Column) {
  if (!column.sortable) return

  const direction =
    props.filters.sort === column.key && props.filters.direction === 'asc' ? 'desc' : 'asc'

  reload({ sort: column.key, direction })
}

const tabs = [
  { value: 'none', label: 'Live' },
  { value: 'only', label: 'Trash' },
  { value: 'with', label: 'Everything' },
]

function showTrashed(value: string) {
  if (value === trashed.value) return

  reload({ trashed: value === 'none' ? undefined : value })
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative w-full max-w-sm sm:w-auto sm:flex-1">
        <Search
          class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
        />
        <Input v-model="search" :placeholder="searchPlaceholder ?? 'Search…'" class="pl-9" />
      </div>

      <div
        v-if="trashable"
        class="inline-flex rounded-md border border-zinc-200 bg-zinc-100 p-0.5 dark:border-zinc-800 dark:bg-zinc-800/60"
      >
        <button
          v-for="tab in tabs"
          :key="tab.value"
          type="button"
          :aria-pressed="trashed === tab.value"
          :class="cn(
            'rounded px-3 py-1.5 text-sm font-medium transition-colors',
            trashed === tab.value
              ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100'
              : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200',
          )"
          @click="showTrashed(tab.value)"
        >
          {{ tab.label }}
          <span
            v-if="tab.value === 'only' && trashedCount"
            class="ml-1 text-xs tabular-nums opacity-70"
          >{{ trashedCount }}</span>
        </button>
      </div>
    </div>

    <div
      class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
    >
      <table class="w-full text-left text-sm">
        <thead
          class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400"
        >
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              scope="col"
              :class="cn('px-4 py-3 font-medium', column.class)"
            >
              <button
                v-if="column.sortable"
                type="button"
                class="inline-flex items-center gap-1 transition-colors hover:text-zinc-800 dark:hover:text-zinc-200"
                @click="toggleSort(column)"
              >
                {{ column.label }}
                <component
                  :is="
                    filters.sort !== column.key
                      ? ChevronsUpDown
                      : filters.direction === 'asc'
                        ? ArrowUp
                        : ArrowDown
                  "
                  class="h-3 w-3"
                />
              </button>
              <span v-else>{{ column.label }}</span>
            </th>
            <th scope="col" class="px-4 py-3 text-right font-medium">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
          <tr
            v-for="(row, index) in rows.data"
            :key="row.id ?? index"
            :class="cn(
              'transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50',
              row.deleted_at && 'bg-red-50/40 dark:bg-red-950/20',
            )"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              :class="cn('px-4 py-3 text-zinc-700 dark:text-zinc-300', column.class)"
            >
              <slot :name="`cell:${column.key}`" :row="row">
                {{ row[column.key] ?? '—' }}
              </slot>
            </td>
            <td class="px-4 py-3 text-right">
              <slot name="actions" :row="row" />
            </td>
          </tr>

          <tr v-if="rows.data.length === 0">
            <td
              :colspan="columns.length + 1"
              class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400"
            >
              {{ emptyMessage ?? 'Nothing here yet.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="rows.total > 0"
      class="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-500 dark:text-zinc-400"
    >
      <p>Showing {{ rows.from }}–{{ rows.to }} of {{ rows.total }}</p>

      <nav v-if="rows.links.length > 3" class="flex flex-wrap gap-1">
        <component
          :is="link.url ? 'button' : 'span'"
          v-for="link in rows.links"
          :key="link.label"
          :class="
            cn(
              'rounded-md px-3 py-1.5 transition-colors',
              link.active
                ? 'bg-brand text-white'
                : link.url
                  ? 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                  : 'opacity-40',
            )
          "
          @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
          v-html="link.label"
        />
      </nav>
    </div>
  </div>
</template>
