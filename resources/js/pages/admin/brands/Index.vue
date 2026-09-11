<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ExternalLink, Pencil, Plus } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import DataTable from '@/components/admin/ui/DataTable.vue'
import type { Column, Paginated } from '@/components/admin/ui/table'

defineOptions({ layout: AdminLayout })

interface BrandRow {
  id: number
  name: string
  website: string | null
  primary_color: string | null
  secondary_color: string | null
  logo: { id: number; name: string | null } | null
}

defineProps<{
  brands: Paginated<BrandRow>
  filters: { search: string | null; sort: string; direction: string }
}>()

const columns: Column[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'website', label: 'Website' },
  { key: 'primary_color', label: 'Colors' },
  { key: 'logo', label: 'Logo' },
]
</script>

<template>
  <Head title="Brands" />

  <PageHeading title="Brands">
    <template #actions>
      <Button :as="Link" :href="route('admin.brands.create')">
        <Plus class="h-4 w-4" />
        New brand
      </Button>
    </template>
  </PageHeading>

  <DataTable
    :columns="columns"
    :rows="brands"
    :filters="filters"
    route-name="admin.brands.index"
    search-placeholder="Search brands…"
    empty-message="No brands yet."
  >
    <template #cell:name="{ row }">
      <Link
        :href="route('admin.brands.edit', row.id)"
        class="font-medium text-zinc-900 hover:text-brand dark:text-zinc-100"
      >
        {{ row.name }}
      </Link>
    </template>

    <template #cell:website="{ row }">
      <a
        v-if="row.website"
        :href="row.website"
        target="_blank"
        rel="noopener"
        class="inline-flex items-center gap-1 text-brand hover:underline"
      >
        Visit <ExternalLink class="h-3 w-3" />
      </a>
      <span v-else>—</span>
    </template>

    <template #cell:primary_color="{ row }">
      <div class="flex items-center gap-1.5">
        <span
          v-for="color in [row.primary_color, row.secondary_color].filter(Boolean)"
          :key="color!"
          class="h-5 w-5 rounded-full border border-zinc-300 dark:border-zinc-600"
          :style="{ backgroundColor: color! }"
          :title="color!"
        />
        <span v-if="!row.primary_color && !row.secondary_color">—</span>
      </div>
    </template>

    <template #cell:logo="{ row }">
      {{ row.logo?.name ?? (row.logo ? `#${row.logo.id}` : '—') }}
    </template>

    <template #actions="{ row }">
      <div class="flex items-center justify-end gap-1">
        <Button :as="Link" :href="route('admin.brands.edit', row.id)" variant="ghost" size="icon">
          <Pencil class="h-4 w-4" />
        </Button>
        <ConfirmDelete :url="route('admin.brands.destroy', row.id)" :title="`Delete “${row.name}”?`" />
      </div>
    </template>
  </DataTable>
</template>
