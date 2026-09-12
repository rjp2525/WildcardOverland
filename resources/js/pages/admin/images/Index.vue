<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Lock, Pencil, Star } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import DataTable from '@/components/admin/ui/DataTable.vue'
import type { Column, Paginated } from '@/components/admin/ui/table'

defineOptions({ layout: AdminLayout })

interface ImageRow {
  id: number
  name: string | null
  type: string
  type_label: string
  width: number | null
  height: number | null
  private: boolean
  featured: boolean
  file: { id: string; original_filename: string; mime: string; readable_size: string } | null
}

defineProps<{
  images: Paginated<ImageRow>
  filters: { search: string | null; sort: string; direction: string }
}>()

const columns: Column[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'width', label: 'Dimensions', sortable: true },
  { key: 'file', label: 'File' },
  { key: 'private', label: 'Visibility' },
  { key: 'featured', label: 'Featured', sortable: true },
]
</script>

<template>
  <Head title="Images" />

  <PageHeading title="Images">
    <template #actions>
      <Button :as="Link" :href="route('admin.files.index')" variant="secondary">Upload a file</Button>
    </template>
  </PageHeading>

  <DataTable
    :columns="columns"
    :rows="images"
    :filters="filters"
    route-name="admin.images.index"
    search-placeholder="Search images…"
    empty-message="No images yet. Upload an image under Files."
  >
    <template #cell:name="{ row }">
      <Link
        :href="route('admin.images.edit', row.id)"
        class="font-medium text-zinc-900 hover:text-brand dark:text-zinc-100"
      >
        {{ row.name ?? `Image #${row.id}` }}
      </Link>
    </template>

    <template #cell:type="{ row }">
      <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
        {{ row.type_label }}
      </span>
    </template>

    <template #cell:width="{ row }">
      <span v-if="row.width && row.height">{{ row.width }} × {{ row.height }}</span>
      <span v-else>—</span>
    </template>

    <template #cell:file="{ row }">
      <span v-if="row.file" class="text-xs">
        {{ row.file.original_filename }}
        <span class="text-zinc-400">({{ row.file.readable_size }})</span>
      </span>
      <span v-else>—</span>
    </template>

    <template #cell:private="{ row }">
      <span v-if="row.private" class="inline-flex items-center gap-1 text-amber-700 dark:text-amber-400">
        <Lock class="h-3.5 w-3.5" /> Private
      </span>
      <span v-else class="text-zinc-500 dark:text-zinc-400">Public</span>
    </template>

    <template #cell:featured="{ row }">
      <Star v-if="row.featured" class="h-4 w-4 fill-amber-400 text-amber-500" />
      <span v-else class="text-zinc-400">—</span>
    </template>

    <template #actions="{ row }">
      <div class="flex items-center justify-end gap-1">
        <Button :as="Link" :href="route('admin.images.edit', row.id)" variant="ghost" size="icon">
          <Pencil class="h-4 w-4" />
        </Button>
        <ConfirmDelete
          :url="route('admin.images.destroy', row.id)"
          :title="`Remove “${row.name ?? `Image #${row.id}`}”?`"
          description="The image record is removed; the underlying file is kept."
        />
      </div>
    </template>
  </DataTable>
</template>
