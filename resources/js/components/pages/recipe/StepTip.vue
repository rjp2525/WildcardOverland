<script setup lang="ts">
import { computed } from 'vue'
import { Lightbulb, TriangleAlert, Sparkles } from 'lucide-vue-next'
import RichText from '@/components/ui/RichText.vue'

export interface Tip {
  kind: string
  label: string
  title: string | null
  body: string
}

const props = defineProps<{ tip: Tip }>()

/*
 * A warning is not advice. Burnt garlic ruins the whole cook, so it does not
 * get to look like a suggestion about which pan to use.
 */
const styles = computed(() => {
  switch (props.tip.kind) {
    case 'warning':
      return {
        icon: TriangleAlert,
        box: 'border-amber-400/60 bg-amber-50 dark:border-amber-400/35 dark:bg-amber-400/10',
        mark: 'text-amber-600 dark:text-amber-400',
      }
    case 'why':
      return {
        icon: Sparkles,
        box: 'border-slate-200 bg-slate-50 dark:border-white/12 dark:bg-white/5',
        mark: 'text-slate-500 dark:text-white/55',
      }
    default:
      return {
        icon: Lightbulb,
        box: 'border-brand/35 bg-brand/5 dark:border-brand/30 dark:bg-brand/10',
        mark: 'text-brand',
      }
  }
})
</script>

<template>
  <div :class="['step-tip flex gap-2.5 rounded-lg border px-3.5 py-3', styles.box]">
    <component :is="styles.icon" :class="['mt-0.5 h-4 w-4 shrink-0', styles.mark]" aria-hidden="true" />

    <div class="min-w-0 text-sm leading-relaxed">
      <p :class="['font-bold uppercase tracking-wide', styles.mark]">
        {{ tip.title || tip.label }}
      </p>
      <RichText :html="tip.body" compact class="mt-1 text-slate-700 dark:text-white/75" />
    </div>
  </div>
</template>
