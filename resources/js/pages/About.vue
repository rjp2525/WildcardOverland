<script setup lang="ts">
import { PageHeader } from '@/components/page-header';
import { Introduction, Statistics, Maya, Partners, BuildTimeline } from '@/components/pages/about';
</script>

<template>
  <Head title="About" />

  <PageHeader />
  <Introduction />
  <Statistics />
  <Maya />
  <Partners />
  <BuildTimeline />
</template>
