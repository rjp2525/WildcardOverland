<script setup lang="ts">
import { TripCard } from '@/components/cards'
import type { TripCardData } from '@/components/cards/TripCard.vue'
import SectionHeading from './SectionHeading.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineProps<{ trips: TripCardData[] }>()
</script>

<template>
  <section v-if="trips.length" class="py-16">
    <div class="container">
      <SectionHeading
        title="Latest trips"
        subtitle="Recent runs, and what they were like."
        :more-href="route('trips.index')"
        more-label="All trips"
      />
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <TripCard v-for="trip in trips" :key="trip.slug" :trip="trip" />
      </div>
    </div>
  </section>
</template>
