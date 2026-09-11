<script setup lang="ts">
import CountUp from 'vue-countup-v3';
import { Infinity } from 'lucide-vue-next';

export interface AboutStats {
  trips: number;
  nights: number;
  photos: number;
}

defineProps<{ stats: AboutStats }>();

const countUpOptions = (delay: number) => ({
  enableScrollSpy: true,
  scrollSpyDelay: delay,
});
</script>

<template>
  <div class="bg-brand dark:shadow">
    <div class="container py-6">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 text-center text-white/80 divide-y lg:divide-y-0 divide-x divide-white/60">
        <!-- Not data-backed yet: no source for mileage. -->
        <div class="flex flex-col justify-center p-4 space-y-1">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="23567" :options="countUpOptions(100)" />
          </span>
          <span class="text-sm font-bold">miles off road</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-t-0">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="stats.trips" :options="countUpOptions(200)" />
          </span>
          <span class="text-sm font-bold">{{ stats.trips === 1 ? 'trip' : 'trips' }}</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-l-0 sm:!border-l sm:!border-t-0">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="stats.nights" :options="countUpOptions(300)" />
          </span>
          <span class="text-sm font-bold">{{ stats.nights === 1 ? 'night camped' : 'nights camped' }}</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 sm:!border-l-0 lg:!border-l">
          <span class="font-extrabold text-4xl justify-center flex">
            <Infinity stroke-width="3" class="h-10 w-auto" />
          </span>
          <span class="text-sm font-bold">friends made</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-l-0 sm:!border-l">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="stats.photos" :options="countUpOptions(400)" />
          </span>
          <span class="text-sm font-bold">{{ stats.photos === 1 ? 'photo' : 'photos' }}</span>
        </div>
        <!-- Not data-backed yet: campsites carry coordinates but no state. -->
        <div class="flex flex-col justify-center p-4 space-y-1">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="16" :options="countUpOptions(500)" />
          </span>
          <span class="text-sm font-bold">states</span>
        </div>
      </div>
    </div>
  </div>
</template>
