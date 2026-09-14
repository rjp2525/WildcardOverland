<script setup lang="ts">
import { computed, onBeforeUnmount, watch } from 'vue'
import { Editor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import {
  Bold,
  Code,
  Heading2,
  Heading3,
  Italic,
  Link as LinkIcon,
  List,
  ListOrdered,
  Quote,
  Redo2,
  Strikethrough,
  Undo2,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

/**
 * What the editor hands back and takes in: TipTap's own document, not HTML.
 *
 * The database stores this, and one renderer on the server turns it into
 * markup. Storing HTML meant storing a string that something downstream had
 * to trust, which is a bad shape for content people type.
 */
export type RichTextDoc = ReturnType<Editor['getJSON']> | null

const props = withDefaults(
  defineProps<{
    placeholder?: string
    invalid?: boolean
    /** Shorter, fewer tools. For a step or a tip rather than a whole story. */
    compact?: boolean
  }>(),
  { compact: false },
)

const model = defineModel<RichTextDoc>()

/** Nothing typed yet, whatever shape the field arrived in. */
function isBlank(doc: RichTextDoc): boolean {
  if (!doc || !Array.isArray(doc.content) || doc.content.length === 0) return true

  return JSON.stringify(doc.content) === JSON.stringify([{ type: 'paragraph' }])
}

const editor = new Editor({
  content: model.value ?? undefined,
  extensions: [
    StarterKit.configure({ heading: { levels: [2, 3] } }),
    Link.configure({ openOnClick: false, autolink: true }),
    Placeholder.configure({ placeholder: props.placeholder ?? 'Write something…' }),
  ],
  editorProps: {
    attributes: {
      class: cn(
        'prose-admin max-w-none px-4 py-3 focus:outline-hidden text-sm text-zinc-800 dark:text-zinc-200',
        props.compact ? 'min-h-[6rem]' : 'min-h-[16rem]',
      ),
    },
  },
  onUpdate: ({ editor }) => {
    const doc = editor.getJSON() as RichTextDoc
    // An empty document is null, not a document holding one empty
    // paragraph, so "nothing here" looks the same everywhere.
    model.value = isBlank(doc) ? null : doc
  },
})

// Reflect external changes (e.g. loading a different record) without
// clobbering the cursor while the user types.
watch(model, (value) => {
  if (JSON.stringify(value ?? null) === JSON.stringify(editor.getJSON())) return
  if (isBlank(value ?? null) && editor.isEmpty) return

  editor.commands.setContent(value ?? '', { emitUpdate: false })
})

onBeforeUnmount(() => editor.destroy())

function promptForLink() {
  const previous = editor.getAttributes('link').href as string | undefined
  const url = window.prompt('Link URL', previous ?? 'https://')

  if (url === null) return

  if (url === '') {
    editor.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }

  editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

const allTools = [
  { icon: Bold, title: 'Bold', run: () => editor.chain().focus().toggleBold().run(), active: () => editor.isActive('bold') },
  { icon: Italic, title: 'Italic', run: () => editor.chain().focus().toggleItalic().run(), active: () => editor.isActive('italic') },
  { icon: Strikethrough, title: 'Strikethrough', run: () => editor.chain().focus().toggleStrike().run(), active: () => editor.isActive('strike') },
  { icon: Heading2, title: 'Heading 2', run: () => editor.chain().focus().toggleHeading({ level: 2 }).run(), active: () => editor.isActive('heading', { level: 2 }) },
  { icon: Heading3, title: 'Heading 3', run: () => editor.chain().focus().toggleHeading({ level: 3 }).run(), active: () => editor.isActive('heading', { level: 3 }) },
  { icon: List, title: 'Bullet list', run: () => editor.chain().focus().toggleBulletList().run(), active: () => editor.isActive('bulletList') },
  { icon: ListOrdered, title: 'Numbered list', run: () => editor.chain().focus().toggleOrderedList().run(), active: () => editor.isActive('orderedList') },
  { icon: Quote, title: 'Quote', run: () => editor.chain().focus().toggleBlockquote().run(), active: () => editor.isActive('blockquote') },
  { icon: Code, title: 'Code', run: () => editor.chain().focus().toggleCode().run(), active: () => editor.isActive('code') },
  { icon: LinkIcon, title: 'Link', run: promptForLink, active: () => editor.isActive('link') },
  { icon: Undo2, title: 'Undo', run: () => editor.chain().focus().undo().run(), active: () => false },
  { icon: Redo2, title: 'Redo', run: () => editor.chain().focus().redo().run(), active: () => false },
]

/*
 * A step or a tip is a few sentences with the odd bold number in it. Headings
 * and block quotes inside one would be strange, and the row is narrow enough
 * that a full toolbar wraps onto three lines.
 */
const compactTools = ['Bold', 'Italic', 'Bullet list', 'Numbered list', 'Link', 'Undo', 'Redo']

const tools = computed(() =>
  props.compact ? allTools.filter((tool) => compactTools.includes(tool.title)) : allTools,
)
</script>

<template>
  <div
    :class="
      cn(
        'overflow-hidden rounded-md border bg-white shadow-xs focus-within:ring-2 focus-within:ring-brand dark:bg-zinc-900',
        invalid ? 'border-red-500' : 'border-zinc-300 dark:border-zinc-700',
      )
    "
  >
    <div
      class="flex flex-wrap gap-0.5 border-b border-zinc-200 bg-zinc-50 p-1.5 dark:border-zinc-800 dark:bg-zinc-800/50"
    >
      <button
        v-for="tool in tools"
        :key="tool.title"
        type="button"
        :title="tool.title"
        :aria-label="tool.title"
        :aria-pressed="tool.active()"
        class="rounded p-1.5 text-zinc-600 transition-colors hover:bg-zinc-200 hover:text-zinc-900 aria-pressed:bg-brand aria-pressed:text-white dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
        @click="tool.run()"
      >
        <component :is="tool.icon" class="h-4 w-4" />
      </button>
    </div>

    <EditorContent :editor="editor" />
  </div>
</template>

<style>
/*
 * Scoped-ish editor typography. Kept here rather than in app.css because it
 * only applies inside the admin editor surface.
 */
.prose-admin :where(h2) {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 1rem 0 0.5rem;
}
.prose-admin :where(h3) {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0.875rem 0 0.5rem;
}
.prose-admin :where(p) {
  margin: 0.5rem 0;
}
.prose-admin :where(ul) {
  list-style: disc;
  padding-left: 1.25rem;
  margin: 0.5rem 0;
}
.prose-admin :where(ol) {
  list-style: decimal;
  padding-left: 1.25rem;
  margin: 0.5rem 0;
}
.prose-admin :where(blockquote) {
  border-left: 3px solid var(--color-brand);
  padding-left: 0.75rem;
  color: var(--color-zinc-500);
  margin: 0.75rem 0;
}
.prose-admin :where(a) {
  color: var(--color-brand);
  text-decoration: underline;
}
.prose-admin :where(code) {
  background: var(--color-zinc-100);
  border-radius: 0.25rem;
  padding: 0.1rem 0.3rem;
  font-size: 0.875em;
}
.dark .prose-admin :where(code) {
  background: var(--color-zinc-800);
}
/* Placeholder for the empty document. */
.prose-admin p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  color: var(--color-zinc-400);
  float: left;
  height: 0;
  pointer-events: none;
}
</style>
