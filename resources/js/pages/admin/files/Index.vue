<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { FileIcon, ImageIcon, Upload } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Card from '@/components/admin/ui/Card.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import DataTable from '@/components/admin/ui/DataTable.vue'
import type { Column, Paginated } from '@/components/admin/ui/table'
import Field from '@/components/admin/ui/Field.vue'
import Select from '@/components/admin/ui/Select.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface FileRow {
  id: string
  name: string | null
  original_filename: string
  mime: string
  type: string
  size: number
  readable_size: string
  is_image: boolean
  image_id: number | null
  created_at: string | null
}

const props = defineProps<{
  files: Paginated<FileRow>
  filters: { search: string | null; sort: string; direction: string }
  maxUploadKb: number
}>()

const columns: Column[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'mime', label: 'Type', sortable: true },
  { key: 'readable_size', label: 'Size', sortable: false },
  { key: 'created_at', label: 'Uploaded', sortable: false },
]

const fileInput = ref<HTMLInputElement | null>(null)
const dragging = ref(false)

const form = useForm<{ file: File | null; type: string }>({
  file: null,
  type: 'content',
})

function upload(selected: File | null) {
  if (!selected) return

  form.file = selected
  form.post(route('admin.files.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      form.reset('file')
      if (fileInput.value) fileInput.value.value = ''
    },
  })
}

function onDrop(event: DragEvent) {
  dragging.value = false
  upload(event.dataTransfer?.files?.[0] ?? null)
}

const maxMb = Math.round(props.maxUploadKb / 1024)
</script>

<template>
  <Head title="Files" />

  <PageHeading title="Files" />

  <div class="space-y-6">
    <Card title="Upload" :description="`Images automatically get an Image record. Max ${maxMb} MB.`">
      <div class="space-y-4">
        <div
          :class="[
            'flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-8 text-center transition-colors',
            dragging
              ? 'border-brand bg-brand/5'
              : 'border-zinc-300 dark:border-zinc-700',
          ]"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="onDrop"
        >
          <Upload class="h-6 w-6 text-zinc-400" />
          <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Drag a file here, or
            <button type="button" class="font-medium text-brand hover:underline" @click="fileInput?.click()">
              browse
            </button>
          </p>
          <p v-if="form.progress" class="text-xs text-zinc-500">
            Uploading… {{ form.progress.percentage }}%
          </p>
          <p v-if="form.errors.file" class="text-sm text-red-600">{{ form.errors.file }}</p>

          <input
            ref="fileInput"
            type="file"
            class="hidden"
            @change="upload(($event.target as HTMLInputElement).files?.[0] ?? null)"
          >
        </div>

        <Field label="Type" for="type" hint="“static” files are site assets; “content” files are editorial.">
          <Select
            id="type"
            v-model="form.type"
            class="max-w-xs"
            :options="[
              { value: 'content', label: 'Content' },
              { value: 'static', label: 'Static' },
            ]"
          />
        </Field>
      </div>
    </Card>

    <DataTable
      :columns="columns"
      :rows="files"
      :filters="filters"
      route-name="admin.files.index"
      search-placeholder="Search files…"
      empty-message="No files uploaded yet."
    >
      <template #cell:name="{ row }">
        <div class="flex items-center gap-2">
          <component :is="row.is_image ? ImageIcon : FileIcon" class="h-4 w-4 shrink-0 text-zinc-400" />
          <div class="min-w-0">
            <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">
              {{ row.name ?? row.original_filename }}
            </p>
            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ row.original_filename }}</p>
          </div>
        </div>
      </template>

      <template #cell:mime="{ row }">
        <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">{{ row.mime }}</code>
      </template>

      <template #actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <Button
            v-if="row.image_id"
            :as="Link"
            :href="route('admin.images.edit', row.image_id)"
            variant="ghost"
            size="sm"
          >
            Image
          </Button>
          <ConfirmDelete
            :url="route('admin.files.destroy', row.id)"
            :title="`Delete “${row.name ?? row.original_filename}”?`"
            description="The stored file and its image record are permanently removed."
          />
        </div>
      </template>
    </DataTable>
  </div>
</template>
