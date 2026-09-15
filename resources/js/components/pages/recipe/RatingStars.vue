<script setup lang="ts">
import { computed } from 'vue'
import { Star } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    /** Out of five. Null when nobody has rated it. */
    value: number | null
    size?: 'sm' | 'md' | 'lg'
    /**
     * Amber everywhere it sits on the page's own background. On a coloured
     * band, amber on orange is unreadable, so `current` draws the row in
     * whatever colour the text around it is.
     */
    tone?: 'amber' | 'current'
  }>(),
  { size: 'md', tone: 'amber' },
)

const dimensions = { sm: 'h-3.5 w-3.5', md: 'h-5 w-5', lg: 'h-7 w-7' }

const empty = { amber: 'text-slate-300 dark:text-white/20', current: 'text-current opacity-30' }

const full = { amber: 'text-amber-400', current: 'text-current' }

/*
 * 4.3 is four stars and a third of a fifth, and rounding that to four or to
 * four and a half is throwing away the only interesting part of the number.
 * So the filled row is drawn over the empty one and clipped to the average.
 */
const filled = computed(() => `${((props.value ?? 0) / 5) * 100}%`)
</script>

<template>
  <span class="relative inline-flex shrink-0 align-middle" aria-hidden="true">
    <span :class="['flex gap-0.5', empty[tone]]">
      <Star v-for="n in 5" :key="n" :class="dimensions[size]" fill="currentColor" stroke-width="0" />
    </span>

    <span
      :class="['absolute inset-y-0 left-0 flex gap-0.5 overflow-hidden', full[tone]]"
      :style="{ width: filled }"
    >
      <Star v-for="n in 5" :key="n" :class="dimensions[size]" fill="currentColor" stroke-width="0" />
    </span>
  </span>
</template>
