<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { Check, Copy, Share } from 'lucide-vue-next'

const props = defineProps<{
  name: string
  items: string[]
}>()

/*
 * Two ways out, because they suit different places. On a phone the share
 * sheet drops it straight into Notes or Reminders. On a laptop there is no
 * share sheet worth using, so it goes to the clipboard and you paste it
 * wherever you keep your list.
 */
const canShare = ref(false)
const state = ref<'idle' | 'copied' | 'failed'>('idle')
let resetTimer: ReturnType<typeof setTimeout> | undefined

if (typeof navigator !== 'undefined' && typeof navigator.share === 'function') {
  canShare.value = true
}

const asText = () => `${props.name}\n\n${props.items.map((item) => `- ${item}`).join('\n')}`

function flash(next: 'copied' | 'failed') {
  state.value = next
  clearTimeout(resetTimer)
  resetTimer = setTimeout(() => (state.value = 'idle'), 2200)
}

async function copy() {
  try {
    await navigator.clipboard.writeText(asText())
    flash('copied')
  } catch {
    flash('failed')
  }
}

async function share() {
  try {
    await navigator.share({ title: `${props.name} shopping list`, text: asText() })
  } catch (error) {
    // Cancelling the sheet throws too, and that is not a failure.
    if ((error as DOMException)?.name !== 'AbortError') {
      copy()
    }
  }
}

onBeforeUnmount(() => clearTimeout(resetTimer))
</script>

<template>
  <div class="mt-5 flex flex-wrap items-center gap-3">
    <button
      v-if="canShare"
      type="button"
      class="inline-flex items-center gap-2 rounded-md bg-brand px-4 py-2 text-sm font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-90"
      @click="share"
    >
      <Share class="h-4 w-4" /> Send to Notes
    </button>

    <button
      type="button"
      class="inline-flex items-center gap-2 rounded-md border border-slate-300 px-4 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition-colors hover:border-brand hover:text-brand dark:border-white/20 dark:text-white/80 dark:hover:border-brand dark:hover:text-brand"
      @click="copy"
    >
      <component :is="state === 'copied' ? Check : Copy" class="h-4 w-4" />
      {{ state === 'copied' ? 'Copied' : 'Copy the list' }}
    </button>

    <p
      v-if="state === 'failed'"
      class="text-xs text-slate-500 dark:text-white/50"
      role="status"
    >
      Your browser would not let go of the clipboard. Select the list and copy it by hand.
    </p>
  </div>
</template>
