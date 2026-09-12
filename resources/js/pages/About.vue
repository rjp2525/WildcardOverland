<script setup lang="ts">
import { PageHeader } from '@/components/page-header';
import { Introduction, Statistics, Maya, Partners, BuildTimeline, StartHere } from '@/components/pages/about';
import type { StartHereTrip } from '@/components/pages/about/StartHere.vue';
import type { AboutStats } from '@/components/pages/about/Statistics.vue';
import type { Partner } from '@/components/pages/about/Partners.vue';
import type { TimelineEntry } from '@/components/pages/about/BuildTimeline.vue';

defineProps<{
  stats: AboutStats;
  partners: Partner[];
  timeline: TimelineEntry[];
  rig: { parts: number; years: number };
  latestTrip: StartHereTrip | null;
}>();
</script>

<template>
  <Head title="About" />

  <PageHeader title="About" />
  <Introduction :parts="rig.parts" :years="rig.years" />
  <Statistics :stats="stats" />
  <Maya />
  <BuildTimeline :timeline="timeline" />
  <StartHere :latest-trip="latestTrip" :stats="stats" />
  <Partners :partners="partners" />
</template>
