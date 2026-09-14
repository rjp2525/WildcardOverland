<script setup lang="ts">
import { computed } from 'vue'
import {
  PopoverArrow,
  PopoverContent,
  PopoverPortal,
  PopoverRoot,
  PopoverTrigger,
} from 'reka-ui'
import { Lightbulb, Sparkles, TriangleAlert } from 'lucide-vue-next'
import RichText from '@/components/ui/RichText.vue'

export interface Tip {
  kind: string
  label: string
  title: string | null
  body: string
}

const props = defineProps<{ tip: Tip }>()

/*
 * A warning is not advice. Burnt garlic ruins the whole cook, so it keeps
 * its own colour rather than sitting in the same grey chip as a suggestion
 * about which spatula to bring.
 */
const styles = computed(() => {
  switch (props.tip.kind) {
    case 'warning':
      return {
        icon: TriangleAlert,
        chip: 'border-amber-400/60 text-amber-700 hover:bg-amber-50 dark:border-amber-400/35 dark:text-amber-300 dark:hover:bg-amber-400/10',
        mark: 'text-amber-600 dark:text-amber-400',
      }
    case 'why':
      return {
        icon: Sparkles,
        chip: 'border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-white/15 dark:text-white/65 dark:hover:bg-white/5',
        mark: 'text-slate-500 dark:text-white/55',
      }
    default:
      return {
        icon: Lightbulb,
        chip: 'border-brand/40 text-brand hover:bg-brand/5 dark:border-brand/35 dark:hover:bg-brand/10',
        mark: 'text-brand',
      }
  }
})

const heading = computed(() => props.tip.title || props.tip.label)
</script>

<template>
  <!--
    On screen a tip is a chip you open when you want it. Nine steps carrying
    twenty-two of these inline buried the actual instructions.
  -->
  <PopoverRoot>
    <PopoverTrigger
      class="print-hide inline-flex max-w-full items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand/50"
      :class="styles.chip"
    >
      <component :is="styles.icon" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
      <span class="truncate">{{ heading }}</span>
    </PopoverTrigger>

    <PopoverPortal>
      <PopoverContent
        :collision-padding="12"
        :side-offset="6"
        class="z-50 w-[min(22rem,calc(100vw-2rem))] rounded-lg border border-slate-200 bg-white p-4 shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 dark:border-white/15 dark:bg-zinc-900"
      >
        <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide" :class="styles.mark">
          <component :is="styles.icon" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          {{ heading }}
        </p>
        <RichText :html="tip.body" compact class="mt-1.5 text-sm text-slate-700 dark:text-white/75" />
        <PopoverArrow class="fill-white dark:fill-zinc-900" :width="10" :height="5" />
      </PopoverContent>
    </PopoverPortal>
  </PopoverRoot>

  <!--
    Paper has nothing to click, so the same tip is written out in full. It is
    hidden on screen and the chip is hidden in print, so only ever one shows.
  -->
  <div class="print-only step-tip">
    <p class="step-tip-heading">{{ heading }}</p>
    <RichText :html="tip.body" compact />
  </div>
</template>
