<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { PageHeader } from '@/components/page-header'
import { TripCard } from '@/components/cards'
import type { TripCardData } from '@/components/cards/TripCard.vue'
import Pagination from '@/components/ui/Pagination.vue'

defineProps<{
  trips: {
    data: TripCardData[]
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
}>()
</script>

<template>
  <Head title="Trips" />

  <PageHeader title="Trips" subtitle="Where the truck has been, and what it was like." />

  <div class="container py-12">
    <div v-if="trips.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <TripCard v-for="trip in trips.data" :key="trip.slug" :trip="trip" />
    </div>

    <p v-else class="py-16 text-center text-slate-500 dark:text-white/60">
      No trips published yet. Check back soon.
    </p>

    <Pagination :links="trips.links" />
  </div>
</template>
