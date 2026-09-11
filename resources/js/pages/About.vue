<script setup lang="ts">
import { PageHeader } from '@/components/page-header';
import { Introduction, Statistics, Maya, Partners, BuildTimeline } from '@/components/pages/about';
import type { Partner } from '@/components/pages/about/Partners.vue';
import type { TimelineEntry } from '@/components/pages/about/BuildTimeline.vue';

defineProps<{
  partners: Partner[];
  timeline: TimelineEntry[];
}>();
</script>

<template>
  <Head title="About" />

  <PageHeader />
  <Introduction />
  <Statistics />
  <Maya />
  <Partners :partners="partners" />
  <BuildTimeline :timeline="timeline" />
</template>
