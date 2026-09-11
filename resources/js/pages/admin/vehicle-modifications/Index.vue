<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Check, ExternalLink, Pencil, Plus } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import DataTable from '@/components/admin/ui/DataTable.vue'
import type { Column, Paginated } from '@/components/admin/ui/table'

defineOptions({ layout: AdminLayout })

interface ModRow {
  id: number
  name: string
  vendor: string | null
  purchased_from: string | null
  cost: number | null
  purchase_date: string | null
  install_date: string | null
  shown_on_timeline: boolean
  url: string | null
}

defineProps<{
  modifications: Paginated<ModRow>
  filters: { search: string | null; sort: string; direction: string }
}>()

const columns: Column[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'vendor', label: 'Vendor', sortable: true },
  { key: 'cost', label: 'Cost', sortable: true },
  { key: 'install_date', label: 'Installed', sortable: true },
  { key: 'shown_on_timeline', label: 'Timeline' },
]

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })
</script>

<template>
  <Head title="Vehicle modifications" />

  <PageHeading title="Modifications">
    <template #actions>
      <Button :as="Link" :href="route('admin.vehicle-modifications.create')">
        <Plus class="h-4 w-4" />
        New modification
      </Button>
    </template>
  </PageHeading>

  <DataTable
    :columns="columns"
    :rows="modifications"
    :filters="filters"
    route-name="admin.vehicle-modifications.index"
    search-placeholder="Search modifications…"
    empty-message="No modifications recorded yet."
  >
    <template #cell:name="{ row }">
      <div class="flex items-center gap-1.5">
        <Link
          :href="route('admin.vehicle-modifications.edit', row.id)"
          class="font-medium text-zinc-900 hover:text-brand dark:text-zinc-100"
        >
          {{ row.name }}
        </Link>
        <a v-if="row.url" :href="row.url" target="_blank" rel="noopener" class="text-zinc-400 hover:text-brand">
          <ExternalLink class="h-3.5 w-3.5" />
        </a>
      </div>
    </template>

    <template #cell:cost="{ row }">
      {{ row.cost === null ? '—' : currency.format(row.cost) }}
    </template>

    <template #cell:shown_on_timeline="{ row }">
      <Check v-if="row.shown_on_timeline" class="h-4 w-4 text-emerald-600" />
      <span v-else class="text-zinc-400">—</span>
    </template>

    <template #actions="{ row }">
      <div class="flex items-center justify-end gap-1">
        <Button :as="Link" :href="route('admin.vehicle-modifications.edit', row.id)" variant="ghost" size="icon">
          <Pencil class="h-4 w-4" />
        </Button>
        <ConfirmDelete
          :url="route('admin.vehicle-modifications.destroy', row.id)"
          :title="`Delete “${row.name}”?`"
        />
      </div>
    </template>
  </DataTable>
</template>
