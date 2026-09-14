<script setup lang="ts">
import { computed } from 'vue'
import { Backpack, Flame, Home, Scaling, StickyNote, Wand2 } from 'lucide-vue-next'
import RichText from '@/components/ui/RichText.vue'

export interface Section {
  kind: string
  label: string
  placement: string
  title: string
  /** Rendered from the stored document by the server. */
  body: string
}

const props = defineProps<{ section: Section }>()

const icon = computed(() => {
  switch (props.section.kind) {
    case 'prep': return Home
    case 'technique': return Wand2
    case 'tips': return Flame
    case 'logistics': return Backpack
    case 'scaling': return Scaling
    default: return StickyNote
  }
})

/*
 * Technique is the one that earns a highlight. It is the bit that makes the
 * dish, and it gets lost if it reads like another paragraph of notes.
 */
const featured = computed(() => props.section.kind === 'technique')
</script>

<template>
  <section
    :class="[
      'recipe-section rounded-xl border px-5 py-6 sm:px-7 sm:py-7',
      featured
        ? 'border-brand/35 bg-brand/5 dark:border-brand/30 dark:bg-brand/10'
        : 'border-slate-200 bg-slate-50/60 dark:border-white/12 dark:bg-white/5',
    ]"
  >
    <div class="flex items-center gap-2.5">
      <component :is="icon" class="h-5 w-5 shrink-0 text-brand" aria-hidden="true" />
      <p class="text-xs font-bold uppercase tracking-widest text-brand">{{ section.label }}</p>
    </div>

    <h2 class="mt-2 font-brand text-2xl font-extrabold uppercase text-slate-900 dark:text-white">
      {{ section.title }}
    </h2>

    <RichText :html="section.body" class="mt-3 text-slate-700 dark:text-white/80" />
  </section>
</template>
