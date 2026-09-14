<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { HomepageBanner } from '@/components/homepage-banner';
import {
  CampsiteMap,
  GalleryStrip,
  LatestRecipes,
  LatestTrips,
  RigTeaser,
} from '@/components/homepage';
import { Statistics, Partners } from '@/components/pages/about';
import type { AboutStats } from '@/components/pages/about/Statistics.vue';
import type { Partner } from '@/components/pages/about/Partners.vue';
import type { TripCardData } from '@/components/cards/TripCard.vue';
import type { RecipeCardData } from '@/components/cards/RecipeCard.vue';
import type { GalleryImage } from '@/components/homepage/GalleryStrip.vue';
import type { Modification } from '@/components/homepage/RigTeaser.vue';
import type { CampsitePoint, MapFocus } from '@/components/homepage/CampsiteMap.vue';
import { AnimatedContent } from '@/components/ui/motion'
import NothingYet from '@/components/homepage/NothingYet.vue'

const props = defineProps<{
  latestTrip: { name: string; url: string } | null;
  hasRecipes: boolean;
  trips: TripCardData[];
  recipes: RecipeCardData[];
  gallery: GalleryImage[];
  modifications: Modification[];
  campsites: CampsitePoint[];
  mapFocus: MapFocus | null;
  stats: AboutStats;
  partners: Partner[];
}>();

/*
 * With nothing published the three main sections each hide themselves and
 * the page falls away to a stats bar over empty space, which reads as
 * broken rather than as new. Say so instead.
 */
const nothingPublished = computed(
  () => props.trips.length === 0 && props.recipes.length === 0 && props.campsites.length === 0,
);
</script>

<template>
  <Head title="Wildcard Overland" />

  <HomepageBanner :latest-trip="latestTrip" :has-recipes="hasRecipes" />

  <div id="below-the-fold">
    <Statistics :stats="stats" />
    <NothingYet v-if="nothingPublished" />
    <AnimatedContent><LatestTrips :trips="trips" /></AnimatedContent>
    <AnimatedContent><GalleryStrip :images="gallery" /></AnimatedContent>
    <AnimatedContent><RigTeaser :modifications="modifications" /></AnimatedContent>
    <AnimatedContent><LatestRecipes :recipes="recipes" /></AnimatedContent>
    <AnimatedContent><CampsiteMap :campsites="campsites" :focus="mapFocus" /></AnimatedContent>
    <Partners :partners="partners" />
  </div>
</template>
