<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import RigArt from './RigArt.vue'
import type { BuildLayer, BuildPart } from './types'

const props = defineProps<{
  layers: BuildLayer[]
  parts: BuildPart[]
  selectedId: number | null
}>()

const emit = defineEmits<{ select: [id: number | null] }>()

const exploded = ref(false)
const pointer = ref({ x: 0, y: 0 })
const parallaxEnabled = ref(false)

/** Back to front, so the roof sits on top of the body in the DOM. */
const ordered = computed(() =>
  [...props.layers].sort((a, b) => a.depth - b.depth),
)

/** Placed parts for one layer, flattened so the template needs no assertions. */
function hotspotsFor(layer: string) {
  return props.parts.flatMap((part) =>
    part.layer === layer && part.hotspot
      ? [{
          part,
          x: part.hotspot.x,
          y: part.hotspot.y,
          // Labels near the right-hand edge would spill out of the stage.
          flip: part.hotspot.x > 76,
        }]
      : [],
  )
}

/**
 * Depth drives both effects: how far a layer slides under the pointer, and
 * how far it travels when the view is exploded. The body is the anchor at
 * 0.65, so the roof lifts off it and the interior and underside drop clear -
 * with a little lateral fan, which is what makes the stack legible rather
 * than just tall.
 */
function layerStyle(depth: number) {
  const spread = exploded.value ? 0.65 - depth : 0
  const x = pointer.value.x * depth * 24 + spread * 60
  const y = pointer.value.y * depth * 15 + spread * 190

  return { transform: `translate3d(${x}px, ${y}px, 0)` }
}

let media: MediaQueryList | undefined
const syncParallax = () => {
  parallaxEnabled.value = media?.matches ?? false
  if (!parallaxEnabled.value) {
    pointer.value = { x: 0, y: 0 }
  }
}

onMounted(() => {
  /*
   * A pointer-driven parallax is meaningless on touch and unwelcome for
   * anyone who has asked for less motion; both fall back to the flat view,
   * where the explode toggle still works.
   */
  media = window.matchMedia('(pointer: fine) and (prefers-reduced-motion: no-preference)')
  syncParallax()
  media.addEventListener('change', syncParallax)
})

onBeforeUnmount(() => media?.removeEventListener('change', syncParallax))

function onMove(event: PointerEvent) {
  if (!parallaxEnabled.value) return

  const box = (event.currentTarget as HTMLElement).getBoundingClientRect()
  pointer.value = {
    x: ((event.clientX - box.left) / box.width) * 2 - 1,
    y: ((event.clientY - box.top) / box.height) * 2 - 1,
  }
}

const reset = () => (pointer.value = { x: 0, y: 0 })

function toggle(id: number) {
  emit('select', props.selectedId === id ? null : id)
}
</script>

<template>
  <div class="relative">
    <!--
      Below about 32rem the diagram would be too short to make sense of, so
      it keeps its size and scrolls sideways inside here rather than shrinking
      - and the page itself never scrolls horizontally.
    -->
    <div class="rig-scroller -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
      <div
        class="rig-stage relative w-full overflow-hidden rounded-xl border border-white/10 bg-dark"
        @pointermove="onMove"
        @pointerleave="reset"
      >
        <div class="absolute inset-0 bg-brand-radial-gradient opacity-25" aria-hidden="true" />
        <div class="rig-grid absolute inset-0" aria-hidden="true" />

        <div
          v-for="layer in ordered"
          :key="layer.value"
          class="absolute inset-0 rig-layer"
          :style="layerStyle(layer.depth)"
        >
          <RigArt :layer="layer.value" />
        </div>

        <!--
          Markers travel with their layer but are drawn above every layer: a
          marker on the underside would otherwise disappear behind the body,
          which is exactly the part someone is trying to look past.
        -->
        <div
          v-for="layer in ordered"
          :key="`pins-${layer.value}`"
          class="absolute inset-0 rig-layer"
          :style="layerStyle(layer.depth)"
        >
          <button
            v-for="spot in hotspotsFor(layer.value)"
            :key="spot.part.id"
            type="button"
            class="rig-hotspot"
            :class="{ 'is-active': selectedId === spot.part.id, 'is-flipped': spot.flip }"
            :style="{ left: `${spot.x}%`, top: `${spot.y}%` }"
            :aria-pressed="selectedId === spot.part.id"
            @click="toggle(spot.part.id)"
          >
            <span class="sr-only">{{ spot.part.name }}</span>
            <span class="rig-hotspot-dot" aria-hidden="true" />
            <span class="rig-hotspot-label" aria-hidden="true">{{ spot.part.name }}</span>
          </button>
        </div>
      </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-slate-500 dark:text-white/50">
        {{
          parallaxEnabled
            ? 'Move your cursor over the truck, then tap a marker for the part.'
            : 'Tap a marker for the part behind it.'
        }}
      </p>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-md border border-brand/40 bg-brand/10 px-4 py-2 text-sm font-bold uppercase tracking-wide text-brand transition-colors hover:bg-brand/20"
        :aria-pressed="exploded"
        @click="exploded = !exploded"
      >
        {{ exploded ? 'Put it back together' : 'Explode the view' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.rig-stage {
  aspect-ratio: 1000 / 480;
  min-width: 32rem;
  /* The artwork's viewBox is 1000x480; hotspot percentages assume that box, so
     the stage has to keep the same ratio for the two to stay aligned. */
  perspective: 1200px;
}

.rig-layer {
  transition: transform 600ms cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform;
}

.rig-grid {
  background-image:
    linear-gradient(rgb(255 255 255 / 0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgb(255 255 255 / 0.05) 1px, transparent 1px);
  background-size: 48px 48px;
  mask-image: radial-gradient(ellipse at center, black, transparent 72%);
}

/*
 * The button is exactly the size of its dot and centred on the coordinate;
 * the label is taken out of flow so its width cannot drag the dot off the
 * point it is meant to be marking.
 */
.rig-hotspot {
  position: absolute;
  translate: -50% -50%;
  width: 0.85rem;
  height: 0.85rem;
  cursor: pointer;
}

/* The dot is deliberately small; the thing you press should not be. */
.rig-hotspot::before {
  content: '';
  position: absolute;
  inset: -0.85rem;
}

.rig-hotspot-dot {
  position: absolute;
  inset: 0;
  border-radius: 9999px;
  background: var(--color-brand);
  border: 2px solid #fff;
  box-shadow: 0 0 0 4px color-mix(in oklab, var(--color-brand) 32%, transparent);
  transition: box-shadow 200ms ease, scale 200ms ease;
}

.rig-hotspot:hover .rig-hotspot-dot,
.rig-hotspot:focus-visible .rig-hotspot-dot,
.rig-hotspot.is-active .rig-hotspot-dot {
  scale: 1.15;
  box-shadow: 0 0 0 8px color-mix(in oklab, var(--color-brand) 30%, transparent);
}

.rig-hotspot-label {
  position: absolute;
  top: 50%;
  left: calc(100% + 0.55rem);
  white-space: nowrap;
  border-radius: 0.375rem;
  background: rgb(0 0 0 / 0.7);
  padding: 0.2rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #fff;
  opacity: 0;
  translate: -0.35rem -50%;
  transition: opacity 200ms ease, translate 200ms ease;
}

.rig-hotspot:hover .rig-hotspot-label,
.rig-hotspot:focus-visible .rig-hotspot-label,
.rig-hotspot.is-active .rig-hotspot-label {
  opacity: 1;
  translate: 0 -50%;
}

.rig-hotspot.is-flipped .rig-hotspot-label {
  left: auto;
  right: calc(100% + 0.55rem);
  translate: 0.35rem -50%;
}
.rig-hotspot.is-flipped:hover .rig-hotspot-label,
.rig-hotspot.is-flipped:focus-visible .rig-hotspot-label,
.rig-hotspot.is-flipped.is-active .rig-hotspot-label {
  translate: 0 -50%;
}

@media (prefers-reduced-motion: reduce) {
  .rig-layer {
    transition-duration: 1ms;
  }
}
</style>
