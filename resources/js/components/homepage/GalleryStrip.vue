<script setup lang="ts">
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'

export type GalleryImage = ResponsiveImageData

defineProps<{ images: GalleryImage[] }>()
</script>

<template>
  <section v-if="images.length" class="border-t border-slate-200 py-16 dark:border-white/10">
    <div class="container">
      <h2 class="mb-8 text-center text-3xl font-extrabold uppercase text-brand">From the road</h2>
    </div>

    <!-- Full-bleed so the photographs get the width they deserve. -->
    <div class="grid grid-cols-2 gap-1 sm:grid-cols-3 lg:grid-cols-4">
      <figure
        v-for="(image, i) in images"
        :key="i"
        class="group relative aspect-square overflow-hidden bg-zinc-200 dark:bg-zinc-800"
      >
        <ResponsiveImage
          :image="image"
          class="transition-transform duration-500 group-hover:scale-105"
        />
        <figcaption
          v-if="image.caption"
          class="pointer-events-none absolute inset-x-0 bottom-0 bg-linear-to-t from-black/75 to-transparent p-3 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100"
        >
          {{ image.caption }}
        </figcaption>
      </figure>
    </div>
  </section>
</template>
