<script setup lang="ts" generic="T extends Record<string, any>">
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next'
import Button from './Button.vue'

const props = defineProps<{
  /** Builds a blank row when "add" is pressed. */
  newRow: () => T
  /** Singular noun used in the row header, e.g. "Ingredient". */
  itemLabel: string
  addLabel?: string
  emptyMessage?: string
  /** Row headers read "Step 1", "Step 2"… when true. */
  numbered?: boolean
}>()

const rows = defineModel<T[]>({ required: true })

function add() {
  rows.value.push(props.newRow())
}

function remove(index: number) {
  rows.value.splice(index, 1)
}

function move(index: number, delta: number) {
  const target = index + delta
  if (target < 0 || target >= rows.value.length) return
  const [row] = rows.value.splice(index, 1)
  rows.value.splice(target, 0, row)
}
</script>

<template>
  <div class="space-y-3">
    <div v-if="rows.length" class="space-y-3">
      <div
        v-for="(row, index) in rows"
        :key="index"
        class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800"
      >
        <div class="mb-3 flex items-center justify-between gap-2">
          <div class="flex items-center gap-1 text-zinc-400">
            <GripVertical class="h-4 w-4" />
            <span class="text-xs font-medium">
              {{ itemLabel }}<template v-if="numbered"> {{ index + 1 }}</template>
            </span>
          </div>
          <div class="flex items-center gap-1">
            <Button type="button" variant="ghost" size="sm" :disabled="index === 0" @click="move(index, -1)">
              Up
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              :disabled="index === rows.length - 1"
              @click="move(index, 1)"
            >
              Down
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              :aria-label="`Remove ${itemLabel.toLowerCase()}`"
              @click="remove(index)"
            >
              <Trash2 class="h-4 w-4 text-red-600" />
            </Button>
          </div>
        </div>

        <slot name="row" :row="row" :index="index" />
      </div>
    </div>

    <p v-else class="text-sm text-zinc-500 dark:text-zinc-400">
      {{ emptyMessage ?? `No ${itemLabel.toLowerCase()}s yet.` }}
    </p>

    <Button type="button" variant="secondary" size="sm" @click="add">
      <Plus class="h-4 w-4" />
      {{ addLabel ?? `Add ${itemLabel.toLowerCase()}` }}
    </Button>
  </div>
</template>
