<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next'

export interface TimelineEntry {
  id: number
  name: string
  description: string | null
  vendor: string | null
  url: string | null
  installed_on: string | null
  installed_label: string | null
}

defineProps<{ timeline: TimelineEntry[] }>()
</script>

<template>
  <div v-if="timeline.length" class="container py-12">
    <div class="flex justify-center">
      <h3 class="text-center text-3xl font-extrabold uppercase text-brand dark:drop-shadow">
        The build
      </h3>
    </div>

    <ol class="mx-auto max-w-2xl pt-8">
      <li
        v-for="(entry, index) in timeline"
        :key="entry.id"
        class="relative pl-6 pb-6 last:pb-0"
      >
        <!-- Connector: drawn for every entry except the last. -->
        <span
          v-if="index < timeline.length - 1"
          class="absolute left-[.22rem] top-2 -bottom-1 border-l border-slate-200 dark:border-zinc-600"
          aria-hidden="true"
        />
        <span
          class="absolute left-0 top-1.5 h-2 w-2 rounded-full border border-brand bg-brand"
          aria-hidden="true"
        />

        <div class="flex flex-wrap items-baseline justify-between gap-x-3">
          <h4 class="font-bold text-black dark:text-white/90">
            <component
              :is="entry.url ? 'a' : 'span'"
              :href="entry.url ?? undefined"
              :target="entry.url ? '_blank' : undefined"
              :rel="entry.url ? 'noopener noreferrer' : undefined"
              :class="entry.url ? 'inline-flex items-center gap-1 hover:text-brand' : ''"
            >
              {{ entry.name }}
              <ExternalLink v-if="entry.url" class="h-3.5 w-3.5" />
            </component>
          </h4>

          <time
            v-if="entry.installed_label"
            :datetime="entry.installed_on ?? undefined"
            class="text-sm text-slate-500 dark:text-white/60"
          >
            {{ entry.installed_label }}
          </time>
        </div>

        <p v-if="entry.vendor" class="text-sm font-medium text-brand/90">{{ entry.vendor }}</p>
        <p v-if="entry.description" class="mt-1 text-slate-500 dark:text-white/70">
          {{ entry.description }}
        </p>
      </li>
    </ol>
  </div>
</template>
