<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Check, Copy, ListChecks, Share } from 'lucide-vue-next'
import type { PageProps } from '@/types/PageProps'

const props = defineProps<{
  /** The recipe's name. The words "shopping list" are added here, once. */
  name: string
  items: string[]
}>()

const page = usePage<PageProps>()

/*
 * Getting a list out of here and into something you can tick off is more
 * awkward than it sounds. Apple Notes has no text-to-checklist conversion at
 * all: not markdown, not an HTML list on the clipboard, not a URL scheme,
 * not the share sheet. The only thing on the platform that can build one is
 * the Shortcuts app, through an append-checklist action of its own.
 *
 * So there are three ways out, in descending order of how good they are:
 *
 *  1. A Shortcut, when one is configured. Apple documents a URL scheme for
 *     handing a shortcut input, so this produces a real checklist.
 *  2. The share sheet, on anything that has one. Plain lines, which Notes
 *     turns into a checklist in one go once they are selected.
 *  3. The clipboard, carrying both plain lines and a real HTML list.
 */
const shortcut = computed(() => page.props.site?.notesShortcut ?? {})
const hasShortcut = computed(() => Boolean(shortcut.value.name))

const canShare = ref(false)
const onApple = ref(false)
const state = ref<'idle' | 'copied' | 'failed'>('idle')
let resetTimer: ReturnType<typeof setTimeout> | undefined

onMounted(() => {
  canShare.value = typeof navigator.share === 'function'
  // The scheme only means anything where Shortcuts exists.
  onApple.value = /iPhone|iPad|iPod|Macintosh/.test(navigator.userAgent)
})

const title = () => `${props.name} shopping list`

const escape = (value: string) =>
  value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

const asText = () => `${title()}\n\n${props.items.join('\n')}`

/**
 * Same content without the blank line. The shortcut splits on newlines and
 * appends one checklist item per line, so an empty line becomes an empty
 * tick box in the note.
 */
const asShortcutText = () => [title(), ...props.items].join('\n')

const asHtml = () =>
  `<h1>${escape(title())}</h1><ul>${props.items
    .map((item) => `<li>${escape(item)}</li>`)
    .join('')}</ul>`

/** The shortcut gets the title on the first line and an item on every line after. */
const shortcutUrl = computed(
  () =>
    'shortcuts://run-shortcut?name=' +
    encodeURIComponent(shortcut.value.name ?? '') +
    '&input=text&text=' +
    encodeURIComponent(asShortcutText()),
)

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
      <a
        v-if="hasShortcut && onApple"
        :href="shortcutUrl"
        class="inline-flex items-center gap-2 rounded-md bg-brand px-4 py-2 text-sm font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-90"
      >
        <ListChecks class="h-4 w-4" /> Send to Notes
      </a>

      <button
        v-else-if="canShare"
        type="button"
        class="inline-flex items-center gap-2 rounded-md bg-brand px-4 py-2 text-sm font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-90"
        @click="share"
      >
        <Share class="h-4 w-4" /> Share the list
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

    <p v-else-if="hasShortcut && onApple" class="mt-2 text-xs text-slate-500 dark:text-white/50">
      Lands in Notes as a real checklist.
      <a
        v-if="shortcut.install"
        :href="shortcut.install"
        target="_blank"
        rel="noopener noreferrer"
        class="text-brand hover:underline"
      >Add the shortcut first</a>
      <span v-if="shortcut.install"> if you have not already.</span>
    </p>

    <p v-else class="mt-2 text-xs text-slate-500 dark:text-white/50">
      One item per line. Notes will not tick-box it on its own, so select the
      lines once it lands and tap the checklist button.
    </p>
  </div>
</template>
