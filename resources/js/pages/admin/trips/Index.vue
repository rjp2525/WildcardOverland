<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Pencil, Plus } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import DataTable from '@/components/admin/ui/DataTable.vue'
import type { Column, Paginated } from '@/components/admin/ui/table'

defineOptions({ layout: AdminLayout })

interface TripRow {
  id: number
  name: string
  slug: string | null
  headline: string | null
  start_date: string | null
  end_date: string | null
  nights: number | null
  campsites_count: number
  is_draft: boolean
  published_at: string | null
}

defineProps<{
  trips: Paginated<TripRow>
  filters: { search: string | null; sort: string; direction: string }
}>()

const columns: Column[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'start_date', label: 'Dates', sortable: true },
  { key: 'nights', label: 'Nights' },
  { key: 'campsites_count', label: 'Campsites' },
  { key: 'is_draft', label: 'Status' },
]
</script>

<template>
  <Head title="Trips" />

  <PageHeading title="Trips">
    <template #actions>
      <Button :as="Link" :href="route('admin.trips.create')">
        <Plus class="h-4 w-4" />
        New trip
      </Button>
    </template>
  </PageHeading>

  <DataTable
    :columns="columns"
    :rows="trips"
    :filters="filters"
    route-name="admin.trips.index"
    search-placeholder="Search trips…"
    empty-message="No trips yet. Create your first one."
  >
    <template #cell:name="{ row }">
      <Link
        :href="route('admin.trips.edit', row.id)"
        class="font-medium text-zinc-900 hover:text-brand dark:text-zinc-100"
      >
        {{ row.name }}
      </Link>
      <p v-if="row.headline" class="text-xs text-zinc-500 dark:text-zinc-400">{{ row.headline }}</p>
    </template>

    <template #cell:start_date="{ row }">
      <span v-if="row.start_date">{{ row.start_date }} → {{ row.end_date ?? '…' }}</span>
      <span v-else>—</span>
    </template>

    <template #cell:is_draft="{ row }">
      <span
        v-if="row.is_draft"
        class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
      >
        Draft
      </span>
      <span
        v-else
        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
      >
        Published
      </span>
    </template>

    <template #actions="{ row }">
      <div class="flex items-center justify-end gap-1">
        <Button :as="Link" :href="route('admin.trips.edit', row.id)" variant="ghost" size="icon">
          <Pencil class="h-4 w-4" />
        </Button>
        <ConfirmDelete
          :url="route('admin.trips.destroy', row.id)"
          :title="`Delete “${row.name}”?`"
          description="The trip is soft deleted, so it can be restored later. Its campsites are kept."
        />
      </div>
    </template>
  </DataTable>
</template>
