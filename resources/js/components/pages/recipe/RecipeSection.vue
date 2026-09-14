<script setup lang="ts">
import { computed } from 'vue'
import { Backpack, ChevronDown, Flame, Home, Scaling, StickyNote, Wand2 } from 'lucide-vue-next'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
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
 * Technique is the one worth a highlight. It is the bit that makes the dish
 * and it disappears if it reads like another row of notes.
 */
const featured = computed(() => props.section.kind === 'technique')
</script>

<template>
  <!--
    Shut until asked for. These are the paragraphs every recipe site buries
    the cooking under, and someone standing over a burner wants the steps.
    The content stays in the DOM while closed, so find-in-page can still
    reach it and print can put all of it on the paper.
  -->
  <Collapsible
    :class="[
      'recipe-section group rounded-xl border transition-colors',
      featured
        ? 'border-brand/35 bg-brand/5 dark:border-brand/30 dark:bg-brand/10'
        : 'border-slate-200 bg-slate-50/60 dark:border-white/12 dark:bg-white/5',
    ]"
  >
    <CollapsibleTrigger
      class="recipe-section-summary flex w-full items-center gap-3 rounded-xl px-5 py-4 text-left transition-colors hover:bg-black/[0.03] focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand/50 dark:hover:bg-white/5 sm:px-6"
    >
      <component :is="icon" class="h-5 w-5 shrink-0 text-brand" aria-hidden="true" />

      <div class="min-w-0 flex-1">
        <p class="recipe-section-kind text-xs font-bold uppercase tracking-widest text-brand">
          {{ section.label }}
        </p>
        <h2 class="font-brand text-lg font-extrabold uppercase text-slate-900 dark:text-white sm:text-xl">
          {{ section.title }}
        </h2>
      </div>

      <ChevronDown
        class="print-hide h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-data-[state=open]:rotate-180 dark:text-white/40"
        aria-hidden="true"
      />
    </CollapsibleTrigger>

    <CollapsibleContent>
      <RichText
        :html="section.body"
        class="recipe-section-body px-5 pb-5 pt-2 text-slate-700 dark:text-white/80 sm:px-6 sm:pb-6 sm:pt-3"
      />
    </CollapsibleContent>
  </Collapsible>
</template>
