<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { Lock, MapPin, Moon } from 'lucide-vue-next'
import { RecipeCard, TripCard } from '@/components/cards'
import type { TripCardData } from '@/components/cards/TripCard.vue'
import type { RecipeCardData } from '@/components/cards/RecipeCard.vue'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'
import RichText from '@/components/ui/RichText.vue'
import { AnimatedContent } from '@/components/ui/motion'

type GalleryImage = ResponsiveImageData

interface Campsite {
  name: string
  nights: number | null
  notes: string | null
  state: string | null
  /** Null unless the viewer is entitled to precise locations. */
  coordinates: { lat: number; lng: number } | null
}

defineProps<{
  trip: {
    name: string
    headline: string | null
    summary: string | null
    content: string | null
    date_label: string | null
    nights: number | null
    hero: ResponsiveImageData | null
    gallery: GalleryImage[]
    campsites: Campsite[]
    hasHiddenLocations: boolean
    recipes: RecipeCardData[]
  }
  more: TripCardData[]
}>()
</script>

<template>
  <Head :title="trip.name" />

  <!-- Hero -->
  <div class="relative w-full bg-dark">
    <div class="relative h-[26rem] w-full overflow-hidden sm:h-[32rem]">
      <ResponsiveImage v-if="trip.hero" :image="trip.hero" priority />
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

      <!-- Authored in the admin editor and rendered by the server. -->
      <RichText
        v-if="trip.content"
        :html="trip.content"
        class="max-w-none text-slate-700 dark:text-white/80"
      />
      <p v-else class="text-slate-500 dark:text-white/60">The write-up for this trip is still coming.</p>

      <AnimatedContent v-if="trip.gallery.length" as="section" class="pt-12">
        <h2 class="mb-5 text-2xl font-extrabold uppercase text-brand">Photos</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <figure v-for="(image, i) in trip.gallery" :key="i" class="overflow-hidden rounded-lg">
            <ResponsiveImage :image="image" class="aspect-square" />
            <figcaption
              v-if="image.caption"
              class="pt-2 text-sm text-slate-500 dark:text-white/60"
            >
              {{ image.caption }}
            </figcaption>
          </figure>
        </div>
      </AnimatedContent>
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
          <p v-if="camp.state" class="mt-1 text-xs text-slate-500 dark:text-white/50">
            {{ camp.state }}
          </p>
          <a
            v-if="camp.coordinates"
            :href="`https://www.openstreetmap.org/?mlat=${camp.coordinates.lat}&mlon=${camp.coordinates.lng}#map=12/${camp.coordinates.lat}/${camp.coordinates.lng}`"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline"
          >
            <MapPin class="h-3 w-3" /> View on map
          </a>
        </li>
      </ol>

      <!-- Exact locations are the members-only part; the sites themselves are not. -->
      <div
        v-if="trip.hasHiddenLocations"
        class="mt-4 rounded-lg border border-dashed border-brand/40 bg-brand/5 p-4"
      >
        <p class="flex items-center gap-1.5 text-sm font-bold uppercase tracking-wide text-brand">
          <Lock class="h-3.5 w-3.5" /> Members only
        </p>
        <p class="mt-1.5 text-sm text-slate-600 dark:text-white/70">
          Exact coordinates and GPX downloads for these campsites come with a
          membership. It is what keeps the quiet spots quiet.
        </p>
      </div>
    </aside>
  </div>

  <section
    v-if="trip.recipes.length"
    class="border-t border-slate-200 py-12 dark:border-white/10"
  >
    <div class="container">
      <h2 class="mb-2 text-2xl font-extrabold uppercase text-brand">Cooked on this trip</h2>
      <p class="mb-6 text-slate-600 dark:text-white/70">
        What came out of the skillet along the way.
      </p>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <RecipeCard v-for="recipe in trip.recipes" :key="recipe.slug" :recipe="recipe" />
      </div>
    </div>
  </section>

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
</style>
