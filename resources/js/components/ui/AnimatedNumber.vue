<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    value: number
    /** Milliseconds for the count-up. */
    duration?: number
    /** Delay before starting, for staggering a row of counters. */
    delay?: number
  }>(),
  { duration: 1800, delay: 0 },
)

/*
 * Starts at the real value rather than zero, so the server-rendered HTML and
 * the no-JS case both show the correct number. The animation only ever
 * *replaces* a correct value with another correct value.
 *
 * This exists because vue-countup-v3's scroll-spy and autoplay fight each
 * other and repeatedly left the counters stranded at 0 or part-way.
 */
const display = ref(props.value)
const el = ref<HTMLElement | null>(null)

let frame: number | undefined
let timer: ReturnType<typeof setTimeout> | undefined
let observer: IntersectionObserver | undefined

function animate() {
  const start = performance.now()
  const from = 0
  const to = props.value

  const tick = (now: number) => {
    const t = Math.min(1, (now - start) / props.duration)
    // easeOutExpo, matching the feel of the previous library.
    const eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t)

    display.value = Math.round(from + (to - from) * eased)

    if (t < 1) {
      frame = requestAnimationFrame(tick)
    } else {
      // Always land exactly on the value, never a rounding artefact.
      display.value = to
    }
  }

  frame = requestAnimationFrame(tick)
}

onMounted(() => {
  const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

  if (reducedMotion || !('IntersectionObserver' in window) || !el.value) {
    return // already showing the correct value
  }

  display.value = 0

  observer = new IntersectionObserver(
    (entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return

      // Once only: no resetting when it scrolls back out of view.
      observer?.disconnect()
      timer = setTimeout(animate, props.delay)
    },
    { threshold: 0.1 },
  )

  observer.observe(el.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
  clearTimeout(timer)
  if (frame !== undefined) cancelAnimationFrame(frame)
})
</script>

<template>
  <span ref="el">{{ display.toLocaleString() }}</span>
</template>
