<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowRight, ChefHat, Map, Wrench } from 'lucide-vue-next'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'
import { AnimatedContent } from '@/components/ui/motion'
import { useRoute } from '@/lib/route'
import type { AboutStats } from './Statistics.vue'

const route = useRoute()

export interface StartHereTrip {
  name: string
  headline: string | null
  url: string
  image: ResponsiveImageData | null
}

const props = defineProps<{
  latestTrip: StartHereTrip | null
  stats: AboutStats
}>()

/** Counts come from the same place the rest of the site counts them. */
const links = [
  {
    icon: Map,
    label: 'Trips',
    href: route('trips.index'),
    blurb: 'Route notes, campsites and what the driving was actually like.',
    count: () => props.stats.trips,
    unit: (n: number) => (n === 1 ? 'trip' : 'trips'),
  },
  {
    icon: ChefHat,
    label: 'Camp recipes',
    href: route('recipes.index'),
    blurb: 'Food worth making a long way from a kitchen, and the kit it needs.',
    count: () => null,
    unit: () => '',
  },
  {
    icon: Wrench,
    label: 'The rig',
    href: route('rig'),
    blurb: 'The truck pulled apart, layer by layer, with a link to every part.',
    count: () => null,
    unit: () => '',
  },
]
</script>

<template>
  <AnimatedContent as="section" class="border-t border-slate-200 py-16 dark:border-white/10">
    <div class="container">
      <h2 class="mb-2 text-3xl font-extrabold uppercase text-brand dark:drop-shadow">Start here</h2>
      <p class="mb-8 text-slate-600 dark:text-white/60">
        Three ways in, depending on what you came for.
      </p>

      <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
        <!-- The most recent trip, given the room it deserves. -->
        <Link
          v-if="latestTrip"
          :href="latestTrip.url"
          class="group relative flex min-h-[18rem] flex-col justify-end overflow-hidden rounded-xl bg-dark p-7"
        >
          <ResponsiveImage
            v-if="latestTrip.image"
            :image="latestTrip.image"
            class="absolute inset-0 transition-transform duration-700 group-hover:scale-105"
            sizes="(min-width: 1024px) 55vw, 100vw"
          />
          <div class="absolute inset-0 bg-linear-to-t from-black/90 via-black/50 to-black/20" />

          <div class="relative">
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.2em] text-brand">Latest trip</p>
            <h3 class="font-brand text-3xl font-extrabold uppercase text-white">
              {{ latestTrip.name }}
            </h3>
            <p v-if="latestTrip.headline" class="mt-2 max-w-md text-white/70">
              {{ latestTrip.headline }}
            </p>
            <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold uppercase tracking-wide text-brand">
              Read it
              <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
            </span>
          </div>
        </Link>

        <div class="grid gap-4 content-start">
          <Link
            v-for="link in links"
            :key="link.label"
            :href="link.href"
            class="group flex items-start gap-4 rounded-xl bg-slate-50 p-5 transition-colors hover:bg-slate-100 dark:bg-white/5 dark:hover:bg-white/10"
          >
            <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/12 text-brand">
              <component :is="link.icon" class="h-5 w-5" />
            </span>

            <span class="min-w-0">
              <span class="flex items-baseline gap-2">
                <span class="font-bold uppercase tracking-wide text-black dark:text-white">
                  {{ link.label }}
                </span>
                <span
                  v-if="link.count()"
                  class="text-xs text-slate-400 dark:text-white/40"
                >{{ link.count() }} {{ link.unit(link.count() as number) }}</span>
              </span>
              <span class="mt-1 block text-sm text-slate-600 dark:text-white/65">
                {{ link.blurb }}
              </span>
            </span>

            <ArrowRight
              class="ml-auto mt-1 h-4 w-4 shrink-0 text-slate-300 transition-transform group-hover:translate-x-0.5 group-hover:text-brand dark:text-white/25"
            />
          </Link>
        </div>
      </div>
    </div>
  </AnimatedContent>
</template>
