<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'
import { motion } from 'motion-v'
import { cn } from '@/lib/utils'

/**
 * A heading that resolves out of a blur, word by word. Adapted from nxui.
 *
 * Same rule as AnimatedContent: the text is real text in the markup, and the
 * animation is applied on top once the browser is running. A heading that
 * only exists after hydration is a heading search engines cannot read.
 */
const props = withDefaults(
  defineProps<{
    text: string
    delay?: number
    by?: 'words' | 'letters'
    direction?: 'top' | 'bottom'
    threshold?: number
    duration?: number
    as?: string
    class?: string
  }>(),
  {
    delay: 90,
    by: 'words',
    direction: 'top',
    threshold: 0.2,
    duration: 0.5,
    as: 'span',
  },
)

const element = ref<HTMLElement | null>(null)
const armed = ref(false)
const revealed = ref(false)

const segments = computed(() =>
  props.by === 'words' ? props.text.split(' ') : props.text.split(''),
)

const from = computed(() => ({
  filter: 'blur(8px)',
  opacity: 0,
  y: props.direction === 'top' ? -14 : 14,
}))

const to = { filter: 'blur(0px)', opacity: 1, y: 0 }

useIntersectionObserver(
  element,
  ([entry]) => {
    if (entry?.isIntersecting) {
      revealed.value = true
    }
  },
  { threshold: props.threshold },
)

onMounted(() => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    revealed.value = true

    return
  }

  armed.value = true
})
</script>

<template>
  <component :is="as" ref="element" :class="cn('inline-flex flex-wrap', props.class)">
    <component
      :is="motion.span"
      v-for="(segment, i) in segments"
      :key="i"
      :initial="armed ? from : false"
      :animate="!armed || revealed ? to : from"
      :transition="{ duration, delay: (i * delay) / 1000, ease: [0.16, 1, 0.3, 1] }"
      class="inline-block will-change-[transform,filter,opacity]"
    >
      {{ segment }}<span v-if="by === 'words'" class="inline-block">&nbsp;</span>
    </component>
  </component>
</template>
