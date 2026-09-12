<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'
import { cn } from '@/lib/utils'

/**
 * Reveals its contents as they scroll into view. Adapted from nxui, with two
 * changes that matter.
 *
 * The original starts at opacity 0, which on a server-rendered page means
 * anyone without JavaScript, and every crawler, gets a blank section. Here
 * the markup ships visible and the animation is something the browser adds
 * afterwards, so nothing depends on it running.
 *
 * It also only hides what is off screen to begin with. Anything already in
 * view when the page loads stays put, because hiding the thing a visitor
 * came to read so it can fade back in is a worse page, not a nicer one.
 */
const props = withDefaults(
  defineProps<{
    distance?: number
    direction?: 'vertical' | 'horizontal'
    reverse?: boolean
    duration?: number
    ease?: string
    scale?: number
    threshold?: number
    delay?: number
    /** Keeps the markup semantic: a revealed section is still a section. */
    as?: string
    class?: string
  }>(),
  {
    distance: 28,
    direction: 'vertical',
    reverse: false,
    duration: 0.7,
    ease: 'cubic-bezier(0.16, 1, 0.3, 1)',
    scale: 1,
    threshold: 0.15,
    delay: 0,
    as: 'div',
  },
)

const container = ref<HTMLElement | null>(null)
const armed = ref(false)
const revealed = ref(false)
const calm = ref(false)

const axis = props.direction === 'horizontal' ? 'X' : 'Y'
const offset = props.reverse ? -props.distance : props.distance

let firstReading = true

useIntersectionObserver(
  container,
  ([entry]) => {
    if (calm.value) {
      return
    }

    if (firstReading) {
      firstReading = false

      // Already on screen at load: leave it exactly as it was served.
      if (entry?.isIntersecting) {
        revealed.value = true

        return
      }

      // Off screen: safe to hide, nobody is looking at it yet.
      armed.value = true

      return
    }

    if (entry?.isIntersecting) {
      revealed.value = true
    }
  },
  { threshold: props.threshold },
)

onMounted(() => {
  // Anyone who has asked for less motion gets the content, not the movement.
  calm.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches
})

const hidden = () => armed.value && !revealed.value
</script>

<template>
  <component
    :is="as"
    ref="container"
    :class="cn(props.class)"
    :style="{
      transform: hidden() ? `translate${axis}(${offset}px) scale(${scale})` : undefined,
      opacity: hidden() ? 0 : undefined,
      transition: armed
        ? `transform ${duration}s ${ease} ${delay}s, opacity ${duration}s ${ease} ${delay}s`
        : undefined,
      willChange: armed && !revealed ? 'transform, opacity' : undefined,
    }"
  >
    <slot />
  </component>
</template>
