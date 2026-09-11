<script setup lang="ts">
import { Vue3Marquee } from 'vue3-marquee'

export interface Partner {
  name: string
  url: string | null
  logo: string
  width: number | null
  height: number | null
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
        <img
          :src="partner.logo"
          :alt="partner.name"
          height="60"
          width="100%"
          loading="lazy"
          decoding="async"
        >
      </component>
    </Vue3Marquee>
  </div>
</template>
