<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Pencil, Plus } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import DataTable from '@/components/admin/ui/DataTable.vue'
import type { Column, Paginated } from '@/components/admin/ui/table'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface RecipeRow {
  id: number
  name: string
  headline: string | null
  meal_type: string
  difficulty: string | null
  total_minutes: number | null
  servings: number | null
  is_draft: boolean
}

defineProps<{
  recipes: Paginated<RecipeRow>
  filters: { search: string | null; sort: string; direction: string }
}>()

const columns: Column[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'meal_type', label: 'Meal', sortable: true },
  { key: 'total_minutes', label: 'Time' },
  { key: 'servings', label: 'Serves' },
  { key: 'is_draft', label: 'Status' },
]
</script>

<template>
  <Head title="Recipes" />

  <PageHeading title="Recipes">
    <template #actions>
      <Button :as="Link" :href="route('admin.recipes.create')">
        <Plus class="h-4 w-4" />
        New recipe
      </Button>
    </template>
  </PageHeading>

  <DataTable
    :columns="columns"
    :rows="recipes"
    :filters="filters"
    route-name="admin.recipes.index"
    search-placeholder="Search recipes…"
    empty-message="No recipes yet. Add your first camp recipe."
  >
    <template #cell:name="{ row }">
      <Link
        :href="route('admin.recipes.edit', row.id)"
        class="font-medium text-zinc-900 hover:text-brand dark:text-zinc-100"
      >
        {{ row.name }}
      </Link>
      <p v-if="row.headline" class="text-xs text-zinc-500 dark:text-zinc-400">{{ row.headline }}</p>
    </template>

    <template #cell:total_minutes="{ row }">
      {{ row.total_minutes === null ? '—' : `${row.total_minutes} min` }}
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
        <Button :as="Link" :href="route('admin.recipes.edit', row.id)" variant="ghost" size="icon">
          <Pencil class="h-4 w-4" />
        </Button>
        <ConfirmDelete
          :url="route('admin.recipes.destroy', row.id)"
          :title="`Delete “${row.name}”?`"
          description="The recipe is soft deleted, so it can be restored later."
        />
      </div>
    </template>
  </DataTable>
</template>
