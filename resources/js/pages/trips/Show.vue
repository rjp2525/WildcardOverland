<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { MapPin, Moon } from 'lucide-vue-next'
import { TripCard } from '@/components/cards'
import type { TripCardData } from '@/components/cards/TripCard.vue'

interface GalleryImage {
  url: string
  alt: string
  caption: string | null
}

interface Campsite {
  name: string
  nights: number | null
  notes: string | null
  latitude: number | null
  longitude: number | null
}

defineProps<{
  trip: {
    name: string
    headline: string | null
    summary: string | null
    content: string | null
    date_label: string | null
    nights: number | null
    hero: { url: string; alt: string } | null
    gallery: GalleryImage[]
    campsites: Campsite[]
  }
  more: TripCardData[]
}>()
</script>

<template>
  <Head :title="trip.name" />

  <!-- Hero -->
  <div class="relative w-full bg-dark">
    <div class="relative h-[26rem] w-full overflow-hidden sm:h-[32rem]">
      <img
        v-if="trip.hero"
        :src="trip.hero.url"
        :alt="trip.hero.alt"
        class="h-full w-full object-cover"
      >
      <div v-else class="h-full w-full bg-page-header bg-cover bg-center" />
      <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/40 to-black/30" />

      <div class="absolute inset-x-0 bottom-0">
        <div class="container pb-10">
          <p
            v-if="trip.date_label"
            class="mb-2 text-sm font-bold uppercase tracking-widest text-brand"
          >
            {{ trip.date_label }}
          </p>
          <h1 class="font-brand text-4xl font-extrabold uppercase text-white drop-shadow sm:text-6xl">
            {{ trip.name }}
          </h1>
          <p v-if="trip.headline" class="mt-3 max-w-2xl text-lg text-white/80">
            {{ trip.headline }}
          </p>
          <div class="mt-4 flex flex-wrap items-center gap-5 text-sm font-medium text-white/70">
            <span v-if="trip.nights" class="inline-flex items-center gap-1.5">
              <Moon class="h-4 w-4" /> {{ trip.nights }} {{ trip.nights === 1 ? 'night' : 'nights' }}
            </span>
            <span v-if="trip.campsites.length" class="inline-flex items-center gap-1.5">
              <MapPin class="h-4 w-4" /> {{ trip.campsites.length }}
              {{ trip.campsites.length === 1 ? 'campsite' : 'campsites' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container grid gap-12 py-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div>
      <p
        v-if="trip.summary"
        class="mb-8 border-l-4 border-brand pl-4 text-lg text-slate-700 dark:text-white/80"
      >
        {{ trip.summary }}
      </p>

      <!-- Content is authored in the admin's rich text editor. -->
      <article
        v-if="trip.content"
        class="trip-content max-w-none text-slate-700 dark:text-white/80"
        v-html="trip.content"
      />
      <p v-else class="text-slate-500 dark:text-white/60">The write-up for this trip is still coming.</p>

      <section v-if="trip.gallery.length" class="pt-12">
        <h2 class="mb-5 text-2xl font-extrabold uppercase text-brand">Photos</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <figure v-for="(image, i) in trip.gallery" :key="i" class="overflow-hidden rounded-lg">
            <img
              :src="image.url"
              :alt="image.alt"
              loading="lazy"
              decoding="async"
              class="aspect-square w-full object-cover"
            >
            <figcaption
              v-if="image.caption"
              class="pt-2 text-sm text-slate-500 dark:text-white/60"
            >
              {{ image.caption }}
            </figcaption>
          </figure>
        </div>
      </section>
    </div>

    <aside v-if="trip.campsites.length" class="lg:pt-2">
      <h2 class="mb-4 text-xl font-extrabold uppercase text-brand">Where we camped</h2>
      <ol class="space-y-4">
        <li
          v-for="(camp, i) in trip.campsites"
          :key="i"
          class="relative rounded-lg bg-slate-50 p-4 dark:bg-white/5"
        >
          <div class="flex items-baseline justify-between gap-3">
            <h3 class="font-bold text-black dark:text-white">{{ camp.name }}</h3>
            <span v-if="camp.nights" class="shrink-0 text-xs text-slate-500 dark:text-white/60">
              {{ camp.nights }} {{ camp.nights === 1 ? 'night' : 'nights' }}
            </span>
          </div>
          <p v-if="camp.notes" class="mt-1 text-sm text-slate-600 dark:text-white/70">
            {{ camp.notes }}
          </p>
          <a
            v-if="camp.latitude !== null && camp.longitude !== null"
            :href="`https://www.openstreetmap.org/?mlat=${camp.latitude}&mlon=${camp.longitude}#map=12/${camp.latitude}/${camp.longitude}`"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline"
          >
            <MapPin class="h-3 w-3" /> View on map
          </a>
        </li>
      </ol>
    </aside>
  </div>

  <section v-if="more.length" class="border-t border-slate-200 py-12 dark:border-white/10">
    <div class="container">
      <h2 class="mb-6 text-2xl font-extrabold uppercase text-brand">More trips</h2>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <TripCard v-for="other in more" :key="other.slug" :trip="other" />
      </div>
    </div>
  </section>
</template>

<style>
/* Typography for admin-authored rich text. */
.trip-content :where(h2) {
  font-size: 1.5rem;
  font-weight: 800;
  text-transform: uppercase;
  margin: 1.75rem 0 0.75rem;
}
.trip-content :where(h3) {
  font-size: 1.2rem;
  font-weight: 700;
  margin: 1.5rem 0 0.5rem;
}
.trip-content :where(p) {
  margin: 0.85rem 0;
  line-height: 1.75;
}
.trip-content :where(ul) {
  list-style: disc;
  padding-left: 1.4rem;
  margin: 0.85rem 0;
}
.trip-content :where(ol) {
  list-style: decimal;
  padding-left: 1.4rem;
  margin: 0.85rem 0;
}
.trip-content :where(blockquote) {
  border-left: 3px solid var(--color-brand);
  padding-left: 1rem;
  font-style: italic;
  margin: 1.25rem 0;
}
.trip-content :where(a) {
  color: var(--color-brand);
  text-decoration: underline;
}
</style>
