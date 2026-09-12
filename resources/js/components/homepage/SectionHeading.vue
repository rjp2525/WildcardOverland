<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowRight } from 'lucide-vue-next'

defineProps<{ title: string; subtitle?: string; moreHref?: string; moreLabel?: string }>()
</script>

<template>
  <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
    <div>
      <h2 class="text-3xl font-extrabold uppercase text-brand">{{ title }}</h2>
      <p v-if="subtitle" class="mt-1 text-slate-600 dark:text-white/70">{{ subtitle }}</p>
    </div>
    <Link
      v-if="moreHref"
      :href="moreHref"
      class="group inline-flex items-center gap-1 text-sm font-bold uppercase tracking-wide text-brand hover:underline"
    >
      {{ moreLabel ?? 'See all' }}
      <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
    </Link>
  </div>
</template>
