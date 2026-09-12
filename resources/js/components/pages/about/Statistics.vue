<script setup lang="ts">
import { Infinity } from 'lucide-vue-next';
import AnimatedNumber from '@/components/ui/AnimatedNumber.vue';

export interface AboutStats {
  trips: number;
  nights: number;
  miles: number;
  photos: number;
  states: number;
}

defineProps<{ stats: AboutStats }>();
</script>

<template>
  <div class="bg-brand dark:shadow">
    <div class="container py-6">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 text-center text-white/80 divide-y lg:divide-y-0 divide-x divide-white/60">
        <div class="flex flex-col justify-center p-4 space-y-1">
          <span class="font-extrabold text-4xl">
            <AnimatedNumber :value="stats.miles" />
          </span>
          <span class="text-sm font-bold">{{ stats.miles === 1 ? 'mile' : 'miles' }} off road</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-t-0">
          <span class="font-extrabold text-4xl">
            <AnimatedNumber :value="stats.trips" :delay="100" />
          </span>
          <span class="text-sm font-bold">{{ stats.trips === 1 ? 'trip' : 'trips' }}</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-l-0 sm:!border-l sm:!border-t-0">
          <span class="font-extrabold text-4xl">
            <AnimatedNumber :value="stats.nights" :delay="200" />
          </span>
          <span class="text-sm font-bold">{{ stats.nights === 1 ? 'night camped' : 'nights camped' }}</span>
        </div>
        <!-- Not a number: renders an infinity glyph, so there is nothing to count. -->
        <div class="flex flex-col justify-center p-4 space-y-1 sm:!border-l-0 lg:!border-l">
          <span class="font-extrabold text-4xl justify-center flex">
            <Infinity stroke-width="3" class="h-10 w-auto" />
          </span>
          <span class="text-sm font-bold">friends made</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-l-0 sm:!border-l">
          <span class="font-extrabold text-4xl">
            <AnimatedNumber :value="stats.photos" :delay="300" />
          </span>
          <span class="text-sm font-bold">{{ stats.photos === 1 ? 'photo' : 'photos' }}</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1">
          <span class="font-extrabold text-4xl">
            <AnimatedNumber :value="stats.states" :delay="400" />
          </span>
          <span class="text-sm font-bold">{{ stats.states === 1 ? 'state' : 'states' }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
