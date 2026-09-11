<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Check, Pencil, Plus, X } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import DataTable from '@/components/admin/ui/DataTable.vue'
import type { Column, Paginated } from '@/components/admin/ui/table'

defineOptions({ layout: AdminLayout })

interface LinkRow {
  id: number
  name: string
  route_name: string
  aria_label: string | null
  order: number
  enabled: boolean
  parent: { id: number; name: string } | null
}

defineProps<{
  links: Paginated<LinkRow>
  filters: { search: string | null; sort: string; direction: string }
}>()

const columns: Column[] = [
  { key: 'order', label: 'Order', sortable: true, class: 'w-20' },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'route_name', label: 'Route', sortable: true },
  { key: 'parent', label: 'Parent' },
  { key: 'enabled', label: 'Enabled' },
]
</script>

<template>
  <Head title="Navigation" />

  <PageHeading title="Navigation links">
    <template #actions>
      <Button :as="Link" :href="route('admin.navigation-links.create')">
        <Plus class="h-4 w-4" />
        New link
      </Button>
    </template>
  </PageHeading>

  <DataTable
    :columns="columns"
    :rows="links"
    :filters="filters"
    route-name="admin.navigation-links.index"
    search-placeholder="Search links…"
    empty-message="No navigation links yet."
  >
    <template #cell:name="{ row }">
      <Link
        :href="route('admin.navigation-links.edit', row.id)"
        class="font-medium text-zinc-900 hover:text-brand dark:text-zinc-100"
      >
        {{ row.name }}
      </Link>
    </template>

    <template #cell:route_name="{ row }">
      <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">{{ row.route_name }}</code>
    </template>

    <template #cell:parent="{ row }">
      {{ row.parent?.name ?? '—' }}
    </template>

    <template #cell:enabled="{ row }">
      <Check v-if="row.enabled" class="h-4 w-4 text-emerald-600" />
      <X v-else class="h-4 w-4 text-zinc-400" />
    </template>

    <template #actions="{ row }">
      <div class="flex items-center justify-end gap-1">
        <Button :as="Link" :href="route('admin.navigation-links.edit', row.id)" variant="ghost" size="icon">
          <Pencil class="h-4 w-4" />
        </Button>
        <ConfirmDelete
          :url="route('admin.navigation-links.destroy', row.id)"
          :title="`Delete “${row.name}”?`"
          description="Any child links will keep pointing at this parent id."
        />
      </div>
    </template>
  </DataTable>
</template>
