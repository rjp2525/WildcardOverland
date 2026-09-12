<script setup lang="ts">
import { Infinity } from 'lucide-vue-next'
import AnimatedNumber from '@/components/ui/AnimatedNumber.vue'

export interface AboutStats {
  trips: number
  nights: number
  miles: number
  photos: number
  states: number
}

const props = defineProps<{ stats: AboutStats }>()

const plural = (n: number, one: string, many: string) => (n === 1 ? one : many)

const cells = () => [
  { value: props.stats.miles, label: plural(props.stats.miles, 'mile off road', 'miles off road') },
  { value: props.stats.trips, label: plural(props.stats.trips, 'trip', 'trips') },
  { value: props.stats.nights, label: plural(props.stats.nights, 'night camped', 'nights camped') },
  // Not a number: renders a glyph, so there is nothing to count up to.
  { value: null, label: 'friends made' },
  { value: props.stats.photos, label: plural(props.stats.photos, 'photo', 'photos') },
  { value: props.stats.states, label: plural(props.stats.states, 'state', 'states') },
]
</script>

<template>
  <div class="bg-brand dark:shadow">
    <div class="container py-6">
      <!--
        The dividers are the gaps, not borders on the cells. Six cells across
        two, three or six columns means the cell that needs a border changes
        with the breakpoint, and hand-patching that with border overrides left
        stubs hanging off the grid on a phone. Letting the container colour
        show through a one pixel gap is right at every width, with nothing to
        keep in step.
      -->
      <div
        class="grid grid-cols-2 gap-px overflow-hidden bg-white/35 text-center text-white/85 sm:grid-cols-3 lg:grid-cols-6"
      >
        <div
          v-for="(cell, i) in cells()"
          :key="cell.label"
          class="flex flex-col justify-center gap-1 bg-brand px-3 py-5"
        >
          <span class="flex justify-center text-4xl font-extrabold">
            <AnimatedNumber v-if="cell.value !== null" :value="cell.value" :delay="i * 100" />
            <Infinity v-else stroke-width="3" class="h-10 w-auto" />
          </span>
          <span class="text-sm font-bold">{{ cell.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
