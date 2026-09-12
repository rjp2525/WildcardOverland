<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { Check, Copy, Share } from 'lucide-vue-next'

const props = defineProps<{
  /** The recipe's name. The words "shopping list" are added here, once. */
  name: string
  items: string[]
}>()

/*
 * Two ways out, because they suit different places. On a phone the share
 * sheet drops it into Notes or Reminders. On a laptop there is no share
 * sheet worth using, so it goes to the clipboard.
 *
 * The plain text is bare lines, deliberately. Apple Notes has no
 * text-to-checklist conversion of any kind: not markdown, not HTML on the
 * clipboard, not through the share sheet. Only the Shortcuts app can build
 * a real checklist, through an action of its own. So `- [ ]` does not
 * become a tick box there, it just sits in the note as three characters of
 * rubbish in front of every line. Bare lines are what Notes turns into a
 * checklist in one go once they are selected, which is the actual workflow.
 *
 * The clipboard also carries an HTML list, for the editors that do read one.
 */
const canShare = ref(false)
const state = ref<'idle' | 'copied' | 'failed'>('idle')
let resetTimer: ReturnType<typeof setTimeout> | undefined

if (typeof navigator !== 'undefined' && typeof navigator.share === 'function') {
  canShare.value = true
}

const title = () => `${props.name} shopping list`

const escape = (value: string) =>
  value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

const asText = () => `${title()}\n\n${props.items.join('\n')}`

const asHtml = () =>
  `<h1>${escape(title())}</h1><ul>${props.items
    .map((item) => `<li>${escape(item)}</li>`)
    .join('')}</ul>`

function flash(next: 'copied' | 'failed') {
  state.value = next
  clearTimeout(resetTimer)
  resetTimer = setTimeout(() => (state.value = 'idle'), 2400)
}

async function copy() {
  try {
    if (typeof ClipboardItem === 'function' && navigator.clipboard?.write) {
      await navigator.clipboard.write([
        new ClipboardItem({
          'text/html': new Blob([asHtml()], { type: 'text/html' }),
          'text/plain': new Blob([asText()], { type: 'text/plain' }),
        }),
      ])
    } else {
      await navigator.clipboard.writeText(asText())
    }

    flash('copied')
  } catch {
    flash('failed')
  }
}

async function share() {
  try {
    // The share sheet only carries plain text, so it gets the markdown form.
    await navigator.share({ title: title(), text: asText() })
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
  <div class="mt-5">
    <div class="flex flex-wrap items-center gap-3">
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
    </div>

    <p
      v-if="state === 'failed'"
      class="mt-2 text-xs text-slate-500 dark:text-white/50"
      role="status"
    >
      Your browser would not let go of the clipboard. Select the list and copy it by hand.
    </p>
    <p v-else class="mt-2 text-xs text-slate-500 dark:text-white/50">
      One item per line. Notes will not tick-box it on its own, so select the
      lines once it lands and tap the checklist button.
    </p>
  </div>
</template>
