<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Card from '@/components/admin/ui/Card.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  stats: Array<{ label: string; value: number; route: string | null }>
  recentTrips: Array<{ id: number; name: string; is_draft: boolean; updated_at: string }>
}>()
</script>

<template>
  <Head title="Dashboard" />

  <PageHeading title="Dashboard" />

  <div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <component
        :is="stat.route ? Link : 'div'"
        v-for="stat in stats"
        :key="stat.label"
        :href="stat.route ? route(stat.route) : undefined"
        class="rounded-lg border border-zinc-200 bg-white p-4 transition-colors dark:border-zinc-800 dark:bg-zinc-900"
        :class="stat.route ? 'hover:border-brand/50' : ''"
      >
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ stat.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ stat.value }}</p>
      </component>
    </div>

    <Card title="Recently updated trips">
      <ul v-if="recentTrips.length" class="divide-y divide-zinc-100 dark:divide-zinc-800">
        <li v-for="trip in recentTrips" :key="trip.id" class="flex items-center justify-between py-2.5">
          <Link
            :href="route('admin.trips.edit', trip.id)"
            class="text-sm font-medium text-zinc-800 hover:text-brand dark:text-zinc-200"
          >
            {{ trip.name }}
          </Link>
          <div class="flex items-center gap-3">
            <span
              v-if="trip.is_draft"
              class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
            >
              Draft
            </span>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ trip.updated_at }}</span>
          </div>
        </li>
      </ul>
      <p v-else class="text-sm text-zinc-500 dark:text-zinc-400">No trips yet.</p>
    </Card>
  </div>
</template>
