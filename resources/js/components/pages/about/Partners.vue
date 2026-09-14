<script setup lang="ts">
import { Vue3Marquee } from 'vue3-marquee'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'

export interface Partner {
  name: string
  url: string | null
  logo: ResponsiveImageData
}

defineProps<{ partners: Partner[] }>()
</script>

<template>
  <section v-if="partners.length" class="border-t border-slate-200 py-10 dark:border-white/10">
    <h2 class="container mb-6 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-white/35">
      Along for the ride
    </h2>

    <Vue3Marquee :duration="34" :pause-on-hover="true">
      <component
        :is="partner.url ? 'a' : 'span'"
        v-for="partner in partners"
        :key="partner.name"
        :href="partner.url ?? undefined"
        :target="partner.url ? '_blank' : undefined"
        :rel="partner.url ? 'noopener noreferrer' : undefined"
        :title="partner.name"
        class="partner-logo mx-7 flex h-16 w-36 shrink-0 items-center justify-center"
      >
        <ResponsiveImage
          :image="partner.logo"
          :alt="partner.name"
          fit="contain"
          class="max-h-10 w-auto"
        />
      </component>
    </Vue3Marquee>
  </section>
</template>

<style scoped>
/*
 * A brand sends whatever logo it has: full colour, a dark wordmark, a light
 * one, any proportions. Rendered as they arrive they fight the page and each
 * other, and a dark mark on the dark theme disappears completely.
 *
 * So each one is flattened to a single colour matching the page and given a
 * fixed box to sit in, which makes any logo fit without anyone editing it.
 * Colour comes back on hover, which is the bit a partner cares about.
 */
.partner-logo :deep(img) {
  /* brightness(0) crushes to black whatever the source colours were;
     invert then lifts it to white for the dark theme. */
  filter: brightness(0) saturate(0) opacity(0.55);
  transition: filter 300ms ease;
}

:global(.dark) .partner-logo :deep(img) {
  filter: brightness(0) invert(1) saturate(0) opacity(0.5);
}

.partner-logo:hover :deep(img),
.partner-logo:focus-visible :deep(img) {
  filter: none;
}

@media (prefers-reduced-motion: reduce) {
  .partner-logo :deep(img) {
    transition: none;
  }
}
</style>
