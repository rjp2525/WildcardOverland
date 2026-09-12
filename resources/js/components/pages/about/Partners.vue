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
  <div v-if="partners.length" class="container py-8">
    <Vue3Marquee>
      <component
        :is="partner.url ? 'a' : 'span'"
        v-for="partner in partners"
        :key="partner.name"
        :href="partner.url ?? undefined"
        :target="partner.url ? '_blank' : undefined"
        :rel="partner.url ? 'noopener noreferrer' : undefined"
        class="px-8"
      >
        <ResponsiveImage
          :image="partner.logo"
          :alt="partner.name"
          fit="contain"
          class="h-15 w-auto"
        />
      </component>
    </Vue3Marquee>
  </div>
</template>
