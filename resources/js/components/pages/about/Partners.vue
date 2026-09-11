<script setup lang="ts">
import { Vue3Marquee } from 'vue3-marquee';

const sponsors = [
  {
    name: 'Katadyn Switzerland',
    url: 'https://www.katadyngroup.com/us/en/brands/Katadyn~b4906/overview',
    logo: new URL('@img/partners/katadyn.png', import.meta.url).href
  },
  {
    name: 'Oru Designs USA',
    url: 'https://www.orudesignsusa.com/',
    logo: new URL('@img/partners/oru.png', import.meta.url).href
  },
  {
    name: 'TriPine',
    url: 'https://tripine.com/',
    logo: new URL('@img/partners/tripine.png', import.meta.url).href
  },
];
</script>

<template>
  <div class="container py-8">
    <Vue3Marquee>
      <a
        v-for="sponsor in sponsors"
        :key="sponsor.name"
        :href="sponsor.url"
        target="_blank"
        class="px-8"
      >
        <img height="60" width="100%" :src="sponsor.logo" :alt="sponsor.name" />
      </a>
    </Vue3Marquee>
  </div>
</template>
