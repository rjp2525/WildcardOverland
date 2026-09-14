<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { Flame, Pencil, Undo2 } from 'lucide-vue-next'
import Button from './Button.vue'
import ConfirmDelete from './ConfirmDelete.vue'

const props = defineProps<{
  /** Reads "Trip", "Recipe"… used in the confirmation wording. */
  noun: string
  name: string
  editUrl: string
  trashUrl: string
  restoreUrl: string
  forceUrl: string
  /** Set on a row that is already in the trash. */
  deletedAt?: string | null
  /** Extra sentence about what a permanent delete takes with it. */
  alsoRemoves?: string
}>()

function restore() {
  router.put(props.restoreUrl, {}, { preserveScroll: true })
}
</script>

<template>
  <div class="flex items-center justify-end gap-1">
    <template v-if="deletedAt">
      <Button type="button" variant="ghost" size="sm" @click="restore">
        <Undo2 class="h-4 w-4" />
        Restore
      </Button>

      <ConfirmDelete
        :url="forceUrl"
        :icon="Flame"
        :label="`Delete ${name} for good`"
        :title="`Delete “${name}” for good?`"
        :description="`This one really is final. ${alsoRemoves ?? ''} There is no restoring it afterwards.`"
        confirm-label="Delete for good"
        busy-label="Deleting…"
      />
    </template>

    <template v-else>
      <Button :as="Link" :href="editUrl" variant="ghost" size="icon" :aria-label="`Edit ${name}`">
        <Pencil class="h-4 w-4" />
      </Button>

      <ConfirmDelete
        :url="trashUrl"
        :label="`Move ${name} to the trash`"
        :title="`Move “${name}” to the trash?`"
        :description="`It comes off the site straight away and you can restore it from the Trash tab. Nothing is deleted for good yet.`"
        confirm-label="Move to trash"
        busy-label="Moving…"
      />
    </template>
  </div>
</template>
