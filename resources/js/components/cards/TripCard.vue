<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { MapPin, Moon } from 'lucide-vue-next'

export interface TripCardData {
  name: string
  slug: string
  headline: string | null
  url: string
  date_label: string | null
  nights: number | null
  campsites_count: number | null
  image: { url: string; alt: string } | null
}

defineProps<{ trip: TripCardData }>()
</script>

<template>
  <Link
    :href="trip.url"
    class="group flex flex-col overflow-hidden rounded-lg bg-white shadow-md transition-shadow hover:shadow-xl dark:bg-dark/60"
  >
    <div class="relative aspect-[2/1] overflow-hidden bg-zinc-200 dark:bg-zinc-800">
      <img
        v-if="trip.image"
        :src="trip.image.url"
        :alt="trip.image.alt"
        loading="lazy"
        decoding="async"
        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
      >
      <div v-else class="flex h-full w-full items-center justify-center bg-brand-radial-gradient">
        <span class="font-brand text-2xl font-extrabold uppercase text-brand/40">Wildcard</span>
      </div>
      <span
        v-if="trip.date_label"
        class="absolute left-3 top-3 rounded-full bg-black/70 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white"
      >
        {{ trip.date_label }}
      </span>
    </div>

    <div class="flex flex-1 flex-col gap-2 p-5">
      <h3 class="text-lg font-extrabold uppercase text-black transition-colors group-hover:text-brand dark:text-white">
        {{ trip.name }}
      </h3>
      <p v-if="trip.headline" class="flex-1 text-sm text-slate-600 dark:text-white/70">
        {{ trip.headline }}
      </p>
      <div class="flex items-center gap-4 pt-1 text-xs font-medium text-slate-500 dark:text-white/60">
        <span v-if="trip.nights" class="inline-flex items-center gap-1">
          <Moon class="h-3.5 w-3.5" /> {{ trip.nights }} {{ trip.nights === 1 ? 'night' : 'nights' }}
        </span>
        <span v-if="trip.campsites_count" class="inline-flex items-center gap-1">
          <MapPin class="h-3.5 w-3.5" /> {{ trip.campsites_count }}
          {{ trip.campsites_count === 1 ? 'camp' : 'camps' }}
        </span>
      </div>
    </div>
  </Link>
</template>
