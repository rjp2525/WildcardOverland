<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <div class="mx-auto w-full relative py-24 text-center bg-center bg-page-header">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
    <div class="relative w-full h-full">
      <div class="absolute overflow-hidden left-0 top-0 w-full text-7xl sm:text-8xl text-zinc-300/5 text-center whitespace-nowrap pointer-events-none font-brand font-extrabold uppercase">Wildcard</div>
      <div class="container text-center pt-3 sm:pt-5">
        <h2 class="text-4xl sm:text-5xl font-black uppercase text-brand">
          {{ title }}
        </h2>
        <p v-if="subtitle" class="mx-auto mt-3 max-w-2xl text-white/70">{{ subtitle }}</p>
      </div>
    </div>
  </div>
</template>
