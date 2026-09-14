<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogOverlay,
  AlertDialogPortal,
  AlertDialogRoot,
  AlertDialogTitle,
  AlertDialogTrigger,
} from 'reka-ui'
import { Trash2 } from 'lucide-vue-next'
import type { Component } from 'vue'
import Button from './Button.vue'

const props = withDefaults(
  defineProps<{
    /** Resolved URL to DELETE. */
    url: string
    title?: string
    description?: string
    label?: string
    /** Icon on the trigger. Defaults to a bin. */
    icon?: Component
    /** Wording on the confirming button, e.g. "Delete for good". */
    confirmLabel?: string
    busyLabel?: string
  }>(),
  { confirmLabel: 'Delete', busyLabel: 'Deleting…' },
)

const open = ref(false)
const processing = ref(false)

function confirm() {
  processing.value = true
  router.delete(props.url, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false
      open.value = false
    },
  })
}
</script>

<template>
  <AlertDialogRoot v-model:open="open">
    <AlertDialogTrigger as-child>
      <Button variant="ghost" size="icon" :aria-label="label ?? 'Delete'">
        <component :is="icon ?? Trash2" class="h-4 w-4 text-red-600" />
      </Button>
    </AlertDialogTrigger>

    <AlertDialogPortal>
      <AlertDialogOverlay
        class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
      />
      <AlertDialogContent
        class="fixed left-1/2 top-1/2 z-50 w-[calc(100vw-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg border border-zinc-200 bg-white p-6 shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 dark:border-zinc-800 dark:bg-zinc-900"
      >
        <AlertDialogTitle class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
          {{ title ?? 'Are you sure?' }}
        </AlertDialogTitle>
        <AlertDialogDescription class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
          {{ description ?? 'This cannot be undone.' }}
        </AlertDialogDescription>

        <div class="mt-6 flex justify-end gap-2">
          <AlertDialogCancel as-child>
            <Button variant="secondary">Cancel</Button>
          </AlertDialogCancel>
          <AlertDialogAction as-child>
            <Button variant="destructive" :disabled="processing" @click.prevent="confirm">
              {{ processing ? busyLabel : confirmLabel }}
            </Button>
          </AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialogPortal>
  </AlertDialogRoot>
</template>
