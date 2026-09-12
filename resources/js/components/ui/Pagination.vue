<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'

defineProps<{
  links: Array<{ url: string | null; label: string; active: boolean }>
}>()
</script>

<template>
  <nav v-if="links.length > 3" class="flex flex-wrap justify-center gap-1 pt-10">
    <component
      :is="link.url ? 'button' : 'span'"
      v-for="link in links"
      :key="link.label"
      :class="
        cn(
          'rounded-md px-3.5 py-2 text-sm font-medium transition-colors',
          link.active
            ? 'bg-brand text-white'
            : link.url
              ? 'text-slate-600 hover:bg-slate-100 dark:text-white/70 dark:hover:bg-white/10'
              : 'text-slate-300 dark:text-white/25',
        )
      "
      @click="link.url && router.get(link.url, {}, { preserveScroll: true })"
      v-html="link.label"
    />
  </nav>
</template>
