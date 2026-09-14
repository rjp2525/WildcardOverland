<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

/**
 * Renders a block of stored rich text.
 *
 * The markup arrives already built by the server, which walks the TipTap
 * document and writes the tags itself from a fixed allowlist. Nothing the
 * database holds reaches the browser as markup, so what lands here is ours.
 * That is the only reason v-html is acceptable, and the reason this is the
 * one component allowed to use it.
 */
withDefaults(
  defineProps<{
    html: string | null | undefined
    class?: HTMLAttributes['class']
    /** Tighter spacing, for a tip or a step rather than a whole page. */
    compact?: boolean
  }>(),
  { compact: false },
)
</script>

<template>
  <div
    v-if="html"
    :class="cn('rich-text', compact && 'rich-text-compact', $props.class)"
    v-html="html"
  />
</template>

<style>
.rich-text :where(p) {
  margin: 0.7rem 0;
  line-height: 1.7;
}
.rich-text :where(p:first-child) {
  margin-top: 0;
}
.rich-text :where(p:last-child) {
  margin-bottom: 0;
}
.rich-text :where(h2) {
  font-size: 1.3rem;
  font-weight: 800;
  margin: 1.4rem 0 0.6rem;
}
.rich-text :where(h3) {
  font-size: 1.1rem;
  font-weight: 700;
  margin: 1.1rem 0 0.5rem;
}
.rich-text :where(ul) {
  list-style: disc;
  padding-left: 1.3rem;
  margin: 0.7rem 0;
}
.rich-text :where(ol) {
  list-style: decimal;
  padding-left: 1.4rem;
  margin: 0.7rem 0;
}
.rich-text :where(li) {
  margin: 0.3rem 0;
}
.rich-text :where(li > p) {
  margin: 0;
}
.rich-text :where(blockquote) {
  border-left: 3px solid var(--color-brand);
  padding-left: 0.9rem;
  margin: 0.9rem 0;
  font-style: italic;
}
.rich-text :where(a) {
  color: var(--color-brand);
  text-decoration: underline;
}
.rich-text :where(strong) {
  font-weight: 700;
}
.rich-text :where(code) {
  border-radius: 0.25rem;
  background: rgb(0 0 0 / 0.06);
  padding: 0.1rem 0.3rem;
  font-size: 0.9em;
}
.dark .rich-text :where(code) {
  background: rgb(255 255 255 / 0.1);
}
.rich-text :where(pre) {
  border-radius: 0.5rem;
  background: rgb(0 0 0 / 0.06);
  padding: 0.75rem 1rem;
  overflow-x: auto;
  margin: 0.8rem 0;
}
.dark .rich-text :where(pre) {
  background: rgb(255 255 255 / 0.08);
}
.rich-text :where(hr) {
  border: 0;
  border-top: 1px solid currentColor;
  opacity: 0.2;
  margin: 1.4rem 0;
}

/* Inside a step or a tip, where the surrounding layout owns the rhythm. */
.rich-text-compact :where(p) {
  margin: 0.45rem 0;
}
.rich-text-compact :where(ul),
.rich-text-compact :where(ol) {
  margin: 0.45rem 0;
}
</style>
